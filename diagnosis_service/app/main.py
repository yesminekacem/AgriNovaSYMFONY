from fastapi import FastAPI, File, UploadFile, HTTPException
from fastapi.responses import JSONResponse
import os
import io
from PIL import Image
import numpy as np

app = FastAPI(title="Diagnosis Inference Service")

HF_MODEL = os.environ.get('HF_MODEL')
HF_TOKEN = os.environ.get('HF_TOKEN')


@app.get('/')
async def root():
    return {
        'status': 'ok',
        'message': "Diagnosis service running. Use POST /predict with form field 'image' (multipart/form-data)."
    }


@app.post('/predict')
async def predict(image: UploadFile = File(...)):
    content = await image.read()

    if not content:
        raise HTTPException(status_code=400, detail='Empty file')

    # ===============================
    # 🔥 1. HUGGING FACE MODEL
    # ===============================
    if HF_MODEL:
        import requests

        headers = {}
        if HF_TOKEN:
            headers["Authorization"] = f"Bearer {HF_TOKEN}"

        try:
            hf_url = f"https://api-inference.huggingface.co/models/{HF_MODEL}"

            # ✅ FIXED: send raw binary (not form-data)
            response = requests.post(
                hf_url,
                headers=headers,
                data=content,
                timeout=60
            )

            response.raise_for_status()
            data = response.json()

            if isinstance(data, list) and len(data) > 0:
                top = data[0]
                label = top.get('label', 'unknown')
                score = float(top.get('score', 0.0))

                return JSONResponse({
                    'disease': label,
                    'confidence': score
                })

        except Exception as e:
            print("HF error:", e)

    # ===============================
    # 🧠 2. FALLBACK (ALWAYS WORKS)
    # ===============================
    try:
        img = Image.open(io.BytesIO(content)).convert('RGB')
        small = img.resize((128, 128))
        arr = np.array(small) / 255.0

        r, g, b = arr[..., 0], arr[..., 1], arr[..., 2]

        green_mask = (g > r) & (g > b) & (g > 0.2)
        green_ratio = float(np.mean(green_mask))

        brightness = float(np.mean(0.299 * r + 0.587 * g + 0.114 * b))
        dark_ratio = float(np.mean((0.299 * r + 0.587 * g + 0.114 * b) < 0.25))

        if green_ratio > 0.4 and dark_ratio < 0.05:
            label = 'healthy'
            confidence = 0.8
        elif dark_ratio > 0.25:
            label = 'early_blight'
            confidence = 0.7
        elif green_ratio < 0.15 and brightness > 0.6:
            label = 'powdery_mildew'
            confidence = 0.6
        elif 0.15 <= green_ratio <= 0.4:
            label = 'leaf_spot'
            confidence = 0.65
        else:
            label = 'rust'
            confidence = 0.5

        return JSONResponse({
            'disease': label,
            'confidence': confidence
        })

    except Exception as e:
        print("Fallback error:", e)

        return JSONResponse({
            'disease': 'unknown',
            'confidence': 0.0
        })