(function () {
    'use strict';

    const form = document.getElementById('pedestrian-form');
    if (!form) return;

    const video = document.getElementById('dpi-video');
    const cameraStage = document.getElementById('dpi-camera-stage');
    const camera = document.getElementById('dpi-camera');
    const frame = document.getElementById('dpi-frame');
    const capture = document.getElementById('dpi-capture');
    const start = document.getElementById('dpi-start');
    const nativeStart = document.getElementById('dpi-native-start');
    const nativeFile = document.getElementById('dpi-native-file');
    const cropPanel = document.getElementById('dpi-crop-panel');
    const cropImage = document.getElementById('dpi-crop-image');
    const useCrop = document.getElementById('dpi-use-crop');
    const review = document.getElementById('dpi-review');
    const preview = document.getElementById('dpi-preview');
    const retake = document.getElementById('dpi-retake');
    const retry = document.getElementById('dpi-retry');
    const edit = document.getElementById('dpi-edit');
    const cameraQuality = document.getElementById('dpi-camera-quality');
    const backReview = document.getElementById('dpi-back-review');
    const save = document.getElementById('dpi-save');
    const status = document.getElementById('dpi-status');
    const errors = document.getElementById('dpi-errors');
    const progress = document.getElementById('dpi-progress');
    const fields = ['Primer_Nombre', 'Segundo_Nombre', 'Primer_Apellido', 'Segundo_Apellido', 'cui', 'Fecha_nac'];
    const cameraKey = 'garita-dpi-camera-' + form.dataset.user;

    let stream = null;
    let worker = null;
    let cropper = null;
    let photo = null;
    let previewUrl = null;
    let cropSourceUrl = null;
    let busy = false;
    let opening = false;
    let originalCanvas = null;
    let editingCurrent = false;
    const manualFields = new Set();

    function message(text) { status.textContent = text; }

    function fieldStatus(field, state, detail) {
        const input = document.getElementById(field);
        input.dataset.ocrState = state;
        input.classList.toggle('dpi-needs-review', state === 'review' || (state === 'missing' && input.required));
        document.getElementById(field + '-ocr').textContent = detail || '';
    }

    fields.forEach(field => document.getElementById(field).addEventListener('input', () => {
        manualFields.add(field);
        fieldStatus(field, 'manual', 'Editado manualmente.');
    }));

    function readingControls(disabled) {
        retry.disabled = edit.disabled = retake.disabled = disabled;
        nativeStart.disabled = start.disabled = camera.disabled = disabled;
        save.disabled = disabled || !photo;
    }

    function discardWorker() {
        const current = worker;
        worker = null;
        if (current) Promise.resolve().then(() => current.terminate()).catch(() => {});
    }

    function showQuality(canvas) {
        const result = DpiOcr.quality(canvas);
        cameraQuality.hidden = false;
        cameraQuality.textContent = 'DPI capturado: ' + canvas.width + ' × ' + canvas.height + ' px. ' + result.warning;
    }

    function canUseLiveCamera() {
        return window.isSecureContext && !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
    }

    function stopCamera() {
        if (stream) stream.getTracks().forEach(track => track.stop());
        stream = null;
        video.pause();
        video.srcObject = null;
        capture.disabled = true;
        frame.hidden = true;
    }

    function videoContentRect() {
        const bounds = video.getBoundingClientRect();
        if (!video.videoWidth || !video.videoHeight) return bounds;
        const sourceRatio = video.videoWidth / video.videoHeight;
        const boxRatio = bounds.width / bounds.height;
        let width = bounds.width;
        let height = bounds.height;
        if (boxRatio > sourceRatio) width = height * sourceRatio;
        else height = width / sourceRatio;
        return {
            left: bounds.left + (bounds.width - width) / 2,
            top: bounds.top + (bounds.height - height) / 2,
            width,
            height
        };
    }

    function fitFrame() {
        if (!video.videoWidth || !video.clientWidth || !video.clientHeight) {
            frame.hidden = true;
            return;
        }
        const content = videoContentRect();
        const width = Math.min(content.width * 0.9, content.height * 0.85 * 1.586);
        frame.style.width = width + 'px';
        frame.style.height = (width / 1.586) + 'px';
        frame.hidden = false;
    }

    function rememberedCamera() {
        try { return localStorage.getItem(cameraKey); } catch (_) { return null; }
    }

    function rememberCamera(id) {
        if (!id) return;
        try { localStorage.setItem(cameraKey, id); } catch (_) { /* Browser storage may be disabled. */ }
    }

    function showCameraFailure(error) {
        if (error && ['NotAllowedError', 'PermissionDeniedError', 'SecurityError'].includes(error.name)) {
            message('No se concedió el permiso de cámara. Puede tomar la foto con el botón de cámara nativa.');
        } else if (error && ['NotFoundError', 'DevicesNotFoundError'].includes(error.name)) {
            message('No se encontró una cámara disponible. Puede elegir una foto con la cámara nativa.');
        } else if (error && ['NotReadableError', 'TrackStartError', 'AbortError'].includes(error.name)) {
            message('La cámara está ocupada por otra aplicación. Ciérrela o tome la foto con la cámara nativa.');
        } else {
            message('No se pudo abrir la cámara. Revise el permiso o tome la foto con la cámara nativa.');
        }
    }

    function showCaptureMode() {
        document.getElementById('dpi-capture-actions').hidden = false;
        cameraStage.hidden = true;
        capture.hidden = true;
        cropPanel.hidden = true;
        review.hidden = true;
        backReview.hidden = !photo;
        start.hidden = !canUseLiveCamera();
        start.disabled = false;
        nativeStart.hidden = false;
        nativeStart.disabled = false;
        camera.hidden = true;
        capture.disabled = true;
        if (!canUseLiveCamera()) {
            message(!window.isSecureContext
                ? 'En HTTP por IP el navegador no permite cámara en vivo. Toque “Tomar foto del DPI” para abrir la cámara del dispositivo.'
                : 'La cámara en vivo no está disponible aquí. Toque “Tomar foto del DPI” para usar la cámara del dispositivo.');
        } else if (!photo) {
            message('Abra la cámara o tome la foto con la cámara del dispositivo.');
        } else {
            message('Puede conservar la foto actual o tomar otra.');
        }
    }

    async function requestStream(id) {
        const media = navigator.mediaDevices;
        const baseVideo = { width: { ideal: 1920 }, height: { ideal: 1080 } };
        if (id) {
            try {
                return await media.getUserMedia({ audio: false, video: Object.assign({}, baseVideo, { deviceId: { exact: id } }) });
            } catch (error) {
                if (!['OverconstrainedError', 'NotFoundError', 'DevicesNotFoundError'].includes(error.name)) throw error;
            }
        }
        try {
            return await media.getUserMedia({ audio: false, video: Object.assign({}, baseVideo, { facingMode: { ideal: 'environment' } }) });
        } catch (error) {
            if (!['OverconstrainedError', 'NotFoundError'].includes(error.name)) throw error;
            return media.getUserMedia({ audio: false, video: true });
        }
    }

    async function listCameras(activeId) {
        camera.hidden = true;
        camera.innerHTML = '';
        try {
            if (!navigator.mediaDevices.enumerateDevices) return;
            const devices = (await navigator.mediaDevices.enumerateDevices()).filter(device => device.kind === 'videoinput');
            if (devices.length < 2) return;
            devices.forEach((device, index) => {
                camera.add(new Option(device.label || 'Cámara ' + (index + 1), device.deviceId));
            });
            if (activeId && devices.some(device => device.deviceId === activeId)) camera.value = activeId;
            if (!camera.value && devices[0]) camera.value = devices[0].deviceId;
            camera.hidden = false;
        } catch (_) {
            // A failed device listing must not interrupt a stream which is already active.
            camera.hidden = true;
        }
    }

    async function openCamera(id) {
        if (opening || busy) return;
        if (!canUseLiveCamera()) {
            showCaptureMode();
            return;
        }
        opening = true;
        start.disabled = true;
        camera.disabled = true;
        nativeStart.disabled = true;
        stopCamera();
        message('Solicitando acceso a la cámara…');
        try {
            stream = await requestStream(id || rememberedCamera());
            video.srcObject = stream;
            cameraStage.hidden = false;
            await video.play();
            await new Promise(resolve => {
                if (video.videoWidth) return resolve();
                video.addEventListener('loadedmetadata', resolve, { once: true });
                window.setTimeout(resolve, 1500);
            });
            fitFrame();

            cameraQuality.hidden = false;
            cameraQuality.textContent = 'Resolución de cámara: ' + video.videoWidth + ' × ' + video.videoHeight + ' px.' +
                (video.videoWidth < 1280 ? ' Para mejorar la lectura, aumente la resolución de DroidCam y acerque el DPI.' : ' Mantenga el DPI quieto dentro del marco.');

            let activeId = null;
            try { activeId = stream.getVideoTracks()[0].getSettings().deviceId || null; } catch (_) { /* Older Safari may omit settings. */ }
            rememberCamera(activeId || id);
            await listCameras(activeId);

            capture.hidden = false;
            capture.disabled = false;
            backReview.hidden = !photo;
            nativeStart.disabled = false;
            message('Cámara lista. Encuadre el DPI y pulse “Capturar y leer”.');
        } catch (error) {
            stopCamera();
            cameraStage.hidden = true;
            showCameraFailure(error);
        } finally {
            opening = false;
            start.disabled = false;
            nativeStart.disabled = false;
            camera.disabled = false;
        }
    }

    function canvasBlob(canvas, quality, type) {
        return new Promise((resolve, reject) => canvas.toBlob(blob => blob ? resolve(blob) : reject(new Error('No se pudo preparar la foto.')), type || 'image/jpeg', quality));
    }

    function fitCanvas(source, maxWidth) {
        return DpiOcr.scale(source, maxWidth, false);
    }

    function loadImage(url) {
        return new Promise((resolve, reject) => {
            const image = new Image();
            image.onload = () => resolve(image);
            image.onerror = reject;
            image.src = url;
        });
    }

    async function normalizePhotoFile(file) {
        const maxDimension = 2400;
        let source;
        let bitmap = null;
        if (window.createImageBitmap) {
            try {
                const probe = await createImageBitmap(file);
                const resize = {};
                if (probe.width >= probe.height && probe.width > maxDimension) resize.resizeWidth = maxDimension;
                else if (probe.height > maxDimension) resize.resizeHeight = maxDimension;
                probe.close();
                bitmap = await createImageBitmap(file, Object.assign({ imageOrientation: 'from-image', resizeQuality: 'high' }, resize));
                source = bitmap;
            } catch (_) {
                source = null;
            }
        }
        let fallbackUrl = null;
        if (!source) {
            fallbackUrl = URL.createObjectURL(file);
            try { source = await loadImage(fallbackUrl); } catch (_) { throw new Error('El archivo no es una imagen que se pueda abrir.'); }
        }
        try {
            const canvas = fitCanvas(source, maxDimension);
            return await canvasBlob(canvas, undefined, 'image/png');
        } finally {
            if (bitmap) bitmap.close();
            if (fallbackUrl) URL.revokeObjectURL(fallbackUrl);
        }
    }

    function destroyCropper() {
        if (cropper) cropper.destroy();
        cropper = null;
        if (cropSourceUrl) URL.revokeObjectURL(cropSourceUrl);
        cropSourceUrl = null;
        cropImage.removeAttribute('src');
    }

    async function editPhoto(file, currentPhoto) {
        if (busy) return;
        busy = true;
        nativeStart.disabled = start.disabled = camera.disabled = true;
        useCrop.disabled = true;
        try {
            message('Preparando la foto…');
            const normalized = await normalizePhotoFile(file);
            destroyCropper();
            cropSourceUrl = URL.createObjectURL(normalized);
            cropImage.src = cropSourceUrl;
            cameraStage.hidden = true;
            capture.hidden = true;
            document.getElementById('dpi-capture-actions').hidden = true;
            review.hidden = true;
            cropPanel.hidden = false;
            cropper = new Cropper(cropImage, {
                aspectRatio: 1.586,
                viewMode: 1,
                autoCropArea: currentPhoto ? 1 : 0.92,
                responsive: true,
                restore: false,
                guides: true,
                center: true,
                background: false,
                movable: true,
                zoomable: true,
                zoomOnTouch: true,
                zoomOnWheel: false,
                rotatable: true,
                scalable: false,
                toggleDragModeOnDblclick: false,
                ready: () => { useCrop.disabled = false; message('Ajuste el DPI con los dedos y use “Girar” si la foto quedó de lado.'); }
            });
            editingCurrent = !!currentPhoto;
            start.hidden = !canUseLiveCamera();
            nativeStart.hidden = false;
            backReview.hidden = !photo;
        } catch (error) {
            message(error.message || 'No se pudo preparar la foto. Puede volver a tomarla.');
            cropPanel.hidden = true;
            if (photo) {
                review.hidden = false;
                backReview.hidden = true;
            }
            else showCaptureMode();
        } finally {
            busy = false;
            nativeStart.disabled = false;
            start.disabled = false;
            camera.disabled = false;
        }
    }

    async function getWorker() {
        if (!worker) {
            const base = form.dataset.ocrBase;
            worker = await Tesseract.createWorker('spa', 1, {
                workerPath: base + '/worker.min.js', corePath: base + '/core', langPath: base + '/lang'
            }, { load_system_dawg: '0', load_freq_dawg: '0' });
        }
        return worker;
    }

    async function readPhoto(canvas, blob, replace) {
        busy = true;
        if (replace !== false) {
            manualFields.clear();
            fields.forEach(field => {
                document.getElementById(field).value = '';
                fieldStatus(field, 'missing', '');
            });
        }
        originalCanvas = canvas;
        photo = blob;
        readingControls(true);
        progress.value = 0;
        progress.hidden = false;
        errors.hidden = true;
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(photo);
        preview.src = previewUrl;
        review.hidden = false;
        cropPanel.hidden = true;
        cameraStage.hidden = true;
        capture.hidden = true;
        document.getElementById('dpi-capture-actions').hidden = true;
        backReview.hidden = true;
        showQuality(canvas);
        try {
            message('Preparando lectura del DPI…');
            const ocr = await getWorker();
            const result = await DpiOcr.read(canvas, ocr, {
                onProgress: (pass, section) => {
                    progress.value = (pass - 1) / 9;
                    message('Leyendo ' + section + ' (' + pass + '/9)…');
                },
                onStop: discardWorker
            });
            fields.forEach(field => {
                if (manualFields.has(field)) return;
                const input = document.getElementById(field);
                const metadata = result.fields[field];
                // A failed retry of the same image must not erase an earlier value.
                const confirmedEmptyOtherName = field === 'Segundo_Nombre' && !result.values[field] &&
                    result.groups.names.status === 'recognized';
                if (result.values[field] || replace !== false || confirmedEmptyOtherName) input.value = result.values[field];
                let state = metadata.status;
                if (input.value && !result.values[field]) state = 'review';
                const detail = state === 'review' ? (metadata.conflict ? 'Lecturas diferentes: revise este dato.' : 'Lectura dudosa: revise este dato.') :
                    state === 'missing' && input.required ? 'No se pudo leer. Complete este dato.' : '';
                fieldStatus(field, state, detail);
            });
            const required = fields.filter(field => document.getElementById(field).required);
            const complete = required.every(field => {
                const input = document.getElementById(field);
                return input.value && input.validity.valid && ['recognized', 'manual'].includes(input.dataset.ocrState);
            });
            const anyValue = fields.some(field => document.getElementById(field).value);
            if (!anyValue) {
                message('La lectura no pudo completarse. Conserve la foto y complete los datos manualmente o reintente la lectura.');
            } else if (complete) {
                message('Lectura terminada. Revise los datos antes de guardar.');
            } else {
                message('Lectura parcial. Revise los campos marcados antes de guardar.' +
                    (result.timedOut ? ' Se alcanzó el límite de 30 segundos; puede reintentar sobre esta foto.' : ''));
            }
        } catch (_) {
            message('La lectura no pudo completarse. La foto sigue en la página; complete los datos manualmente o reintente la lectura.');
            discardWorker();
        } finally {
            busy = false;
            progress.hidden = true;
            readingControls(false);
        }
    }

    async function useCurrentCrop() {
        if (busy || !cropper) return;
        busy = true;
        useCrop.disabled = true;
        nativeStart.disabled = start.disabled = camera.disabled = true;
        try {
            const cropped = cropper.getCroppedCanvas({
                maxWidth: 2400,
                maxHeight: Math.round(2400 / 1.586),
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
                fillColor: '#fff'
            });
            if (!cropped) throw new Error('No se pudo recortar la foto.');
            const canvas = fitCanvas(cropped, 2400);
            const blob = await canvasBlob(fitCanvas(canvas, 1600), 0.92);
            const replace = !editingCurrent;
            destroyCropper();
            await readPhoto(canvas, blob, replace);
        } catch (error) {
            message(error.message || 'No se pudo preparar la foto para leerla.');
            useCrop.disabled = false;
        } finally {
            busy = false;
            nativeStart.disabled = false;
            start.disabled = false;
            camera.disabled = false;
        }
    }

    async function captureLiveDpi() {
        if (busy || opening || !stream || !video.videoWidth) return;
        busy = true;
        capture.disabled = start.disabled = camera.disabled = nativeStart.disabled = save.disabled = true;
        errors.hidden = true;
        try {
            message('Mantenga el DPI quieto: seleccionando la foto más nítida…');
            let best = null, bestScore = -1;
            for (let attempt = 0; attempt < 5; attempt++) {
                if (!stream || !video.videoWidth || !video.videoHeight) throw new Error('La cámara se interrumpió. Abra la cámara de nuevo.');
                fitFrame();
                const content = videoContentRect();
                const guide = frame.getBoundingClientRect();
                const scaleX = video.videoWidth / content.width;
                const scaleY = video.videoHeight / content.height;
                const sx = Math.max(0, Math.round((guide.left - content.left) * scaleX));
                const sy = Math.max(0, Math.round((guide.top - content.top) * scaleY));
                const sw = Math.min(video.videoWidth - sx, Math.round(guide.width * scaleX));
                const sh = Math.min(video.videoHeight - sy, Math.round(guide.height * scaleY));
                if (sw < 10 || sh < 10) throw new Error('No se pudo encuadrar el DPI. Vuelva a abrir la cámara.');
                const canvas = document.createElement('canvas');
                canvas.width = sw; canvas.height = sh;
                canvas.getContext('2d', { alpha: false }).drawImage(video, sx, sy, sw, sh, 0, 0, sw, sh);
                const score = DpiOcr.quality(canvas).sharpness;
                if (score > bestScore) { best = canvas; bestScore = score; }
                if (attempt < 4) await new Promise(resolve => window.setTimeout(resolve, 200));
            }
            const blob = await canvasBlob(fitCanvas(best, 1600), 0.92);
            stopCamera();
            await readPhoto(best, blob);
        } catch (error) {
            message(error.message || 'No se pudo capturar el DPI. Abra la cámara y vuelva a intentarlo.');
        } finally {
            busy = false;
            capture.disabled = true;
            nativeStart.disabled = false;
            start.disabled = false;
            camera.disabled = false;
            save.disabled = !photo;
        }
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (busy || !photo || !form.reportValidity()) return;
        busy = true;
        readingControls(true);
        errors.hidden = true;
        try {
            const data = new FormData(form);
            data.append('photo_dpi', photo, 'dpi.jpg');
            const response = await fetch(form.action, { method: 'POST', headers: { Accept: 'application/json' }, body: data });
            const result = await response.json();
            if (!response.ok) {
                errors.textContent = result.errors ? Object.values(result.errors).flat().join(' ') : result.message;
                errors.hidden = false;
                return;
            }
            window.location.assign(result.redirect_url);
        } catch (_) {
            errors.textContent = 'No se pudo guardar la visita. Revise la conexión e intente nuevamente.';
            errors.hidden = false;
        } finally {
            busy = false;
            readingControls(false);
        }
    });

    start.addEventListener('click', () => openCamera(camera.value || rememberedCamera()));
    camera.addEventListener('change', () => openCamera(camera.value));
    capture.addEventListener('click', captureLiveDpi);
    retry.addEventListener('click', () => {
        if (!busy && originalCanvas && photo) readPhoto(originalCanvas, photo, false);
    });
    edit.addEventListener('click', async () => {
        if (busy || !originalCanvas) return;
        busy = true;
        readingControls(true);
        try {
            const blob = await canvasBlob(originalCanvas, undefined, 'image/png');
            busy = false;
            await editPhoto(blob, true);
        } catch (_) {
            message('No se pudo abrir el encuadre. Se conserva la foto actual.');
        } finally {
            busy = false;
            readingControls(false);
        }
    });
    nativeStart.addEventListener('click', () => {
        if (busy) return;
        stopCamera();
        cameraStage.hidden = true;
        capture.hidden = true;
        camera.hidden = true;
        message('Tome una foto o elija una imagen del dispositivo.');
        // Keep the file picker inside the user gesture for iOS Safari.
        nativeFile.click();
    });
    nativeFile.addEventListener('change', () => {
        const file = nativeFile.files && nativeFile.files[0];
        nativeFile.value = '';
        if (file) editPhoto(file);
    });
    useCrop.addEventListener('click', useCurrentCrop);
    document.getElementById('dpi-straighten-left').addEventListener('click', () => { if (cropper) cropper.rotate(-1); });
    document.getElementById('dpi-straighten-right').addEventListener('click', () => { if (cropper) cropper.rotate(1); });
    document.getElementById('dpi-rotate-left').addEventListener('click', () => { if (cropper) cropper.rotate(-90); });
    document.getElementById('dpi-rotate-right').addEventListener('click', () => { if (cropper) cropper.rotate(90); });
    document.getElementById('dpi-zoom-out').addEventListener('click', () => { if (cropper) cropper.zoom(-0.1); });
    document.getElementById('dpi-zoom-in').addEventListener('click', () => { if (cropper) cropper.zoom(0.1); });
    document.getElementById('dpi-cancel-crop').addEventListener('click', () => {
        destroyCropper();
        cropPanel.hidden = true;
        if (photo) {
            review.hidden = false;
            backReview.hidden = true;
            message('Se conserva la foto anterior.');
        } else {
            showCaptureMode();
        }
    });
    retake.addEventListener('click', () => {
        if (busy) return;
        stopCamera();
        document.getElementById('dpi-capture-actions').hidden = false;
        showCaptureMode();
    });
    backReview.addEventListener('click', () => {
        stopCamera();
        destroyCropper();
        document.getElementById('dpi-capture-actions').hidden = true;
        cameraStage.hidden = true;
        cropPanel.hidden = true;
        capture.hidden = true;
        review.hidden = false;
        backReview.hidden = true;
        message('Revise la foto y los datos leídos.');
    });

    window.addEventListener('resize', () => window.requestAnimationFrame(fitFrame));
    window.addEventListener('orientationchange', () => window.setTimeout(fitFrame, 250));
    document.addEventListener('visibilitychange', () => {
        if (document.hidden && stream) {
            stopCamera();
            cameraStage.hidden = true;
            document.getElementById('dpi-capture-actions').hidden = false;
            start.hidden = !canUseLiveCamera();
            message('Cámara pausada. Vuelva a abrirla cuando regrese a esta página.');
        }
    });
    window.addEventListener('pagehide', () => {
        stopCamera();
        discardWorker();
        destroyCropper();
        if (previewUrl) URL.revokeObjectURL(previewUrl);
    });

    showCaptureMode();
    $('#pedestrian-resident').select2({ width: '100%', placeholder: 'Buscar por nombre o código de casa' }).on('change', function () {
        document.getElementById('pedestrian-address').value = this.selectedOptions[0] ? this.selectedOptions[0].dataset.address || '' : '';
    });
}());
