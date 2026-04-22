// Small camera helper for capturing an image and putting it into a hidden input
(function(){
    function $(sel, ctx=document){ return ctx.querySelector(sel); }
    function $all(sel, ctx=document){ return Array.from(ctx.querySelectorAll(sel)); }

    function createStatusOverlay(){
        let existing = document.getElementById('faceStatusOverlay');
        if(existing) return existing;
        const o = document.createElement('div');
        o.id = 'faceStatusOverlay';
        o.style.position = 'fixed';
        o.style.left = '12px';
        o.style.bottom = '12px';
        o.style.padding = '10px 14px';
        o.style.background = 'rgba(0,0,0,0.7)';
        o.style.color = '#fff';
        o.style.borderRadius = '8px';
        o.style.zIndex = '13000';
        o.style.fontSize = '13px';
        o.style.display = 'none';
        document.body.appendChild(o);
        return o;
    }

    function initCameraWidget(root){
        const video = $(".face-video", root);
        const canvas = $(".face-canvas", root);
        const hidden = $("input[name='face_image_base64']", root);
        const enrollForm = root.closest('form');
        let stream = null;
        let detectInterval = null;
        let isActive = false;
        let attempts = 0;
        let maxAttempts = 30; // default
        let pauseUntil = 0;
        let requestInFlight = false; // prevent overlapping detect requests
         const statusOverlay = createStatusOverlay();

        function showStatus(msg, timeout=2000){
            if(!statusOverlay) return;
            statusOverlay.textContent = msg;
            statusOverlay.style.display = '';
            if(timeout>0){
                setTimeout(()=>{ statusOverlay.style.display = 'none'; }, timeout);
            }
        }

        async function startCamera(){
            if(isActive) return;
            if(!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia){
                alert('Camera API not supported in this browser.');
                return;
            }
            try{
                stream = await navigator.mediaDevices.getUserMedia({video: { facingMode: 'user' }, audio: false});
                video.srcObject = stream;
                await video.play();
                video.style.transform = 'scaleX(-1)';
                video.style.webkitTransform = 'scaleX(-1)';
                root.classList.add('camera-active');
                isActive = true;
            }catch(e){
                console.error('Camera start failed', e);
                alert('Unable to access camera: ' + (e && e.message ? e.message : e));
            }
        }

        function stopCamera(){
            if(detectInterval){ clearInterval(detectInterval); detectInterval = null; }
            if(stream){
                stream.getTracks().forEach(t => t.stop());
                stream = null;
            }
            try{ video.pause(); video.srcObject = null; }catch(e){}
            root.classList.remove('camera-active');
            isActive = false;
        }

        function captureFrame(){
            const w = video.videoWidth || 640;
            const h = video.videoHeight || 480;
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            ctx.save();
            ctx.translate(w, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(video, 0, 0, w, h);
            ctx.restore();
            const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
            const stripped = dataUrl.replace(/^data:image\/(png|jpeg|jpg);base64,/, '');
            if(hidden) hidden.value = stripped;
            return stripped;
        }

        async function detectFaceOnce(){
            const detectUrl = root.dataset.detectUrl;
            if(!detectUrl) return {ok:false, error:'no-detect-url'};
            if(requestInFlight){
                return {ok:false, error:'busy'};
            }
            requestInFlight = true;
            const base64 = captureFrame();
            const fd = new FormData();
            fd.append('face_image_base64', base64);
            try{
                const resp = await fetch(detectUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
                if(!resp.ok){
                    // Try to parse JSON error body if present to get a meaningful message/code
                    let parsed = null;
                    try{
                        parsed = await resp.json();
                    }catch(e){
                        // not JSON
                        parsed = null;
                    }
                    const txt = parsed ? (parsed.message || JSON.stringify(parsed)) : await resp.text();
                    return {ok:false, status: resp.status, text: txt, data: parsed};
                }
                const data = await resp.json();
                return {ok:true, data};
            }catch(e){
                console.error('Face detection failed', e);
                return {ok:false, error: e && e.message ? e.message : 'network-error'};
            }finally{
                requestInFlight = false;
            }
        }

        function showAccountSelection(matches){
            const existing = document.querySelector('.account-selection-overlay');
            if(existing) existing.remove();
            const overlay = document.createElement('div');
            overlay.className = 'account-selection-overlay';
            overlay.style.position = 'fixed';
            overlay.style.inset = '0';
            overlay.style.background = 'rgba(0,0,0,0.5)';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.style.zIndex = '12000';

            const box = document.createElement('div');
            box.style.background = '#fff';
            box.style.padding = '16px';
            box.style.borderRadius = '12px';
            box.style.maxWidth = '420px';
            box.style.width = '90%';

            const title = document.createElement('h3');
            title.textContent = 'Select an account';
            title.style.marginTop = '0';
            box.appendChild(title);

            matches.forEach(match => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = `${match.fullName || 'User'} (${Math.round(match.score)}%)`;
                button.style.display = 'block';
                button.style.width = '100%';
                button.style.margin = '8px 0';
                button.style.padding = '10px';
                button.onclick = () => selectAccount(match.id);
                box.appendChild(button);
            });

            overlay.appendChild(box);
            overlay.onclick = (e) => { if(e.target===overlay) overlay.remove(); };
            document.body.appendChild(overlay);
        }

        async function selectAccount(accountId){
            const selectUrl = root.dataset.selectUrl;
            if(!selectUrl) return;
            const fd = new FormData();
            fd.append('selected_user', accountId);
            try{
                const resp = await fetch(selectUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
                const data = await resp.json();
                if(data.success && data.redirect){
                    showStatus('Redirecting...', 1000);
                    try{
                        await fetch(data.redirect, { method: 'GET', credentials: 'same-origin', cache: 'no-store' });
                    }catch(e){}
                    if(data.debug){ console.info('Server debug:', data.debug); showStatus('Server session: ' + (data.debug.sessionId ? 'ok' : 'no'), 1500); }
                    window.location.href = data.redirect;
                } else {
                    // show server message if present
                    if(data && data.message) showStatus(data.message, 3000);
                    else alert('Failed to log in.');
                }
            }catch(e){
                console.error('Account selection failed', e);
            }
        }

        async function detectLoop(){
            // reset counters
            attempts = 0;
            pauseUntil = 0;
            if(detectInterval) clearInterval(detectInterval);

            const run = async () => {
                if(!isActive) return;
                const now = Date.now();
                if(pauseUntil && now < pauseUntil) return; // paused
                if(requestInFlight) return; // avoid overlapping requests
                if(typeof maxAttempts === 'number' && attempts >= maxAttempts){
                    showStatus('No face detected (stopped). Try again or enroll face.', 4000);
                    clearInterval(detectInterval);
                    detectInterval = null;
                    return;
                }

                attempts++;
                showStatus('Detecting face... (attempt ' + attempts + ')', 1000);
                const res = await detectFaceOnce();
                if(!res.ok){
                    // if busy, just skip without penalizing attempts
                    if(res.error === 'busy'){
                        attempts--; // don't count this as an attempt
                        return;
                    }
                    console.warn('Face detect request failed', res);

                    // If server returned a 400 with a helpful message (e.g. missing data), stop trying
                    if(res.status === 400 && res.data && res.data.message){
                        showStatus(res.data.message, 4000);
                        clearInterval(detectInterval);
                        detectInterval = null;
                        try{ if(root.__faceControls) root.__faceControls.stop(); }catch(e){}
                        return;
                    }

                    // handle CSRF or permission errors as fatal
                    if(res.status === 419 || res.status === 403 || res.status === 401){
                        const msg = (res.data && res.data.message) ? res.data.message : ('Server returned ' + res.status);
                        showStatus(msg, 4000);
                        clearInterval(detectInterval);
                        detectInterval = null;
                        try{ if(root.__faceControls) root.__faceControls.stop(); }catch(e){}
                        return;
                    }

                    // pause a bit to avoid flooding for transient network errors
                    pauseUntil = Date.now() + 2000;
                    return;
                }

                const data = res.data;
                console.debug('Face detect response:', data);

                // If server indicates there are no enrolled faces, stop trying
                if(data.code === 'no_enrolled'){
                    showStatus(data.message || 'No enrolled faces available.', 4000);
                    clearInterval(detectInterval);
                    detectInterval = null;
                    // stop camera to free permission
                    try{ if(root.__faceControls) root.__faceControls.stop(); }catch(e){}
                    return;
                }

                if(data.success && data.redirect){
                    // found single match -> wait for server-side auth to be visible then navigate
                    showStatus('Redirecting...', 800);
                    const expectedUser = data.debug && data.debug.userId ? data.debug.userId : null;
                    await waitForAuthThenNavigate(data.redirect, expectedUser);
                    return;
                }

                if(data.success && data.matches && data.matches.length > 0){
                    // stop detection and show selection
                    clearInterval(detectInterval);
                    detectInterval = null;
                    showAccountSelection(data.matches);
                    return;
                }

                // if we received success=false with message, show briefly and pause a bit
                if(!data.success && data.message){
                    console.info('Face detect server:', data.message);
                    showStatus(data.message, 1500);
                    // stop on repeated explicit 'no_match' to avoid continuous polling
                    if(data.code === 'no_match'){
                        // give user clear feedback then stop
                        showStatus(data.message || 'No matching accounts found.', 3000);
                        clearInterval(detectInterval);
                        detectInterval = null;
                        try{ if(root.__faceControls) root.__faceControls.stop(); }catch(e){}
                        return;
                    }
                    // short pause before next attempt
                    pauseUntil = Date.now() + 1500;
                    return;
                }

                // unknown response: pause briefly
                pauseUntil = Date.now() + 1000;
            };

            await run();
            // Only start the periodic interval if the run did not stop detection
            // run() may have cleared detectInterval and stopped the camera when it saw a fatal condition.
            if (!detectInterval && isActive) {
                detectInterval = setInterval(run, 900);
            }
        }

        // Public controls exposed on the widget root
        root.__faceControls = {
            start: async function(options={detect:false, maxAttempts:30}){
                maxAttempts = options.maxAttempts || 30;
                await startCamera();
                if(options.detect){
                    detectLoop();
                }
                // reveal save/enroll button if present
                const saveBtn = root.querySelector('.face-save-btn');
                if(saveBtn) saveBtn.style.display = '';
                const openBtn = root.querySelector('.face-open-btn');
                if(openBtn) openBtn.style.display = 'none';
            },
            stop: function(){ stopCamera(); },
            capture: function(){ return captureFrame(); },
            captureAndSubmit: function(){
                captureFrame();
                // if there's a form enclosing the widget, submit it
                if(enrollForm) enrollForm.submit();
            }
        };

        // attach simple open/save buttons behavior if present
        const openBtn = root.querySelector('.face-open-btn');
        const saveBtn = root.querySelector('.face-save-btn');
        if(openBtn){
            openBtn.addEventListener('click', function(e){
                // decide whether to start detection automatically based on dataset
                const doDetect = root.dataset.detectUrl ? true : false;
                root.__faceControls.start({detect: doDetect});
            });
        }
        if(saveBtn){
            // hidden by default, only shown after start()
            saveBtn.style.display = 'none';
            saveBtn.addEventListener('click', async function(e){
                e.preventDefault();
                // capture a frame first
                captureFrame();

                // If this widget is configured for detection (login flow), run a single detect
                if(root.dataset.detectUrl){
                    // Prefer a full form submit so the browser handles cookies/redirects reliably.
                    const form = root.querySelector('.face-detect-form') || root.closest('form');
                    if(form){
                        // stop polling and camera to avoid races
                        try{ if(detectInterval){ clearInterval(detectInterval); detectInterval = null; } }catch(e){}
                        try{ if(root.__faceControls) root.__faceControls.stop(); }catch(e){}
                        // ensure hidden input contains captured base64 (captureFrame already set it)
                        // submit the form normally
                        form.submit();
                        return;
                    }

                    // If no form is available for some reason, fallback to AJAX detection
                    showStatus('Scanning face...', 1500);
                    const res = await detectFaceOnce();
                    if(!res.ok){
                        console.warn('Face detect request failed', res);
                        if(res.status === 400 && res.data && res.data.message){
                            showStatus(res.data.message, 4000);
                            try{ if(root.__faceControls) root.__faceControls.stop(); }catch(e){}
                            return;
                        }
                        if(res.status === 419 || res.status === 403 || res.status === 401){
                            const msg = (res.data && res.data.message) ? res.data.message : ('Server returned ' + res.status);
                            showStatus(msg, 4000);
                            try{ if(root.__faceControls) root.__faceControls.stop(); }catch(e){}
                            return;
                        }
                        showStatus('Network error. Try again.', 2000);
                        return;
                    }

                    const data = res.data;
                    if(data.code === 'no_enrolled'){
                        showStatus(data.message || 'No enrolled faces available.', 4000);
                        try{ if(root.__faceControls) root.__faceControls.stop(); }catch(e){}
                        return;
                    }

                    if(data.success && data.redirect){
                        showStatus('Redirecting...', 800);
                        const expectedUser = data.debug && data.debug.userId ? data.debug.userId : null;
                        await waitForAuthThenNavigate(data.redirect, expectedUser);
                        return;
                    }

                    if(data.success && data.matches && data.matches.length > 0){
                        showAccountSelection(data.matches);
                        return;
                    }

                    if(!data.success && data.message){
                        showStatus(data.message, 2500);
                        if(data.code === 'no_match'){
                            try{ if(root.__faceControls) root.__faceControls.stop(); }catch(e){}
                        }
                        return;
                    }

                    showStatus('No matching accounts found.', 2500);
                    return;
                }

                // Default/enroll behavior: capture and submit enclosing form
                root.__faceControls.captureAndSubmit();
            });
         }

        // cleanup on unload
        window.addEventListener('beforeunload', stopCamera);
    }

    document.addEventListener('DOMContentLoaded', function(){
        $all('.face-camera-widget').forEach(initCameraWidget);
    });
})();
