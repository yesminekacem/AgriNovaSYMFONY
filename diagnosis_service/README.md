Diagnosis Inference Service
=========================

This small FastAPI service exposes `/predict` which accepts an image file and returns a JSON object with `disease` and `confidence`.

Modes:
- Local model: set `MODEL_PATH` to a TorchScript `.pt` file and optionally `LABELS_PATH` (JSON mapping index->label).
- Hugging Face: set `HF_MODEL` and `HF_TOKEN` to use Hugging Face Inference API.

Quick start (recommended in a Python venv):

```bash
cd diagnosis_service
python -m pip install -r requirements.txt
# Option A: run with HF model
export HF_MODEL="your-hf-username/your-model"
export HF_TOKEN="hf_..."
uvicorn app.main:app --host 0.0.0.0 --port 8000

# Option B: run with local TorchScript model
export MODEL_PATH="/path/to/model.pt"
export LABELS_PATH="/path/to/labels.json"
uvicorn app.main:app --host 0.0.0.0 --port 8000
```

Example response:

```
{ "disease": "early_blight", "confidence": 0.92 }
```

Replace the model/labels with your trained plant disease classifier for production.
