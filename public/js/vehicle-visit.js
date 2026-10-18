(function () {
    'use strict';

    const form = document.getElementById('vehicle-visit-form');
    if (!form) return;

    const roles = ['licencia', 'placa', 'visitante'];
    const photoFields = { licencia: 'photo1', placa: 'photo2', visitante: 'photo3' };
    const enabledPhotos = {
        licencia: form.dataset.photoLicencia === 'true',
        placa: form.dataset.photoPlaca === 'true',
        visitante: form.dataset.photoVisitante === 'true'
    };
    const storageKey = 'garita.vehicle-visit.v1.' + form.dataset.userId;
    const fields = {
        scanner: document.getElementById('data'),
        resident: document.getElementById('selectResidente'),
        plate: document.getElementById('placa'),
        cone: document.getElementById('cono'),
        delivery: document.getElementById('es_repartidor'),
        manualName: document.getElementById('nombre-manual')
    };
    const saveButton = document.getElementById('guardar');
    const cancelLink = document.getElementById('prueba');
    const messageBox = document.getElementById('responset');
    const videoEls = {
        licencia: document.getElementById('video1'),
        placa: document.getElementById('video2'),
        visitante: document.getElementById('video3')
    };
    const canvasEls = {
        licencia: document.getElementById('canvas1'),
        placa: document.getElementById('canvas2'),
        visitante: document.getElementById('canvas3')
    };
    const mutableControls = Array.from(document.querySelectorAll('.visit-input'));
    const cameras = Object.fromEntries(roles.map(role => [role, null]));

    let tokenizer = [];
    let nombreManual = '';
    let photos = { licencia: null, placa: null, visitante: null };
    let saving = false;
    let registrationComplete = false;
    let lockedForRetry = false;
    let pendingSubmission = null;
    let saveAttempted = false;
    let saveTimeout = null;
    let alertTimer = null;
    let storageWarningShown = false;
    let draftDisabled = false;

    function displayMessage(message, kind, details) {
        window.clearTimeout(alertTimer);
        messageBox.replaceChildren();
        messageBox.className = 'alert ' + (kind === 'success' ? 'alert-success' : 'alert-danger');
        messageBox.appendChild(document.createTextNode(message));

        (details || []).forEach(function (detail) {
            const line = document.createElement('div');
            line.textContent = detail;
            messageBox.appendChild(line);
        });

        messageBox.hidden = false;
        if (kind === 'success') {
            alertTimer = window.setTimeout(function () { messageBox.hidden = true; }, 5000);
        }
    }

    function clearMessage() {
        window.clearTimeout(alertTimer);
        messageBox.replaceChildren();
        messageBox.hidden = true;
        messageBox.className = '';
    }

    function addLoginLink() {
        const line = document.createElement('div');
        line.className = 'mt-2';
        const link = document.createElement('a');
        link.href = form.dataset.loginUrl;
        link.target = '_blank';
        link.rel = 'noopener';
        link.textContent = 'Iniciar sesión en otra pestaña';
        line.appendChild(link);
        messageBox.appendChild(line);
    }

    function updateSaveButton() {
        saveButton.removeAttribute('aria-label');
        if (registrationComplete) {
            saveButton.disabled = true;
            saveButton.textContent = 'Registro guardado';
        } else if (saving) {
            saveButton.disabled = true;
            saveButton.textContent = 'Guardando…';
        } else if (lockedForRetry) {
            saveButton.setAttribute('aria-label', 'Verificar o reintentar el mismo guardado');
            saveButton.disabled = false;
            saveButton.textContent = 'Verificar / reintentar guardado';
        } else {
            saveButton.disabled = false;
            saveButton.textContent = 'Guardar';
        }

        mutableControls.forEach(function (control) {
            control.disabled = saving || lockedForRetry || registrationComplete;
        });

        if (cancelLink) {
            cancelLink.setAttribute('aria-disabled', saving ? 'true' : 'false');
            cancelLink.tabIndex = saving ? -1 : 0;
        }
    }

    function stateForDraft() {
        return {
            version: 1,
            user_id: String(form.dataset.userId),
            submission_id: form.dataset.submissionId,
            scanner: fields.scanner.value,
            resident: fields.resident.value,
            plate: fields.plate.value,
            cone: fields.cone.value,
            delivery: fields.delivery.checked,
            tokens: tokenizer,
            manual_name: nombreManual,
            manual_name_input: fields.manualName.value,
            photos: photos,
            pending_submission: pendingSubmission,
            attempt_sent: saveAttempted
        };
    }

    function saveDraft() {
        if (draftDisabled) return;
        try {
            sessionStorage.setItem(storageKey, JSON.stringify(stateForDraft()));
        } catch (_) {
            if (!storageWarningShown) {
                displayMessage('El navegador no permitió guardar un borrador. Mantenga esta pestaña abierta para no perder los datos.', 'error');
                storageWarningShown = true;
            }
        }
    }

    function scheduleDraftSave() {
        window.clearTimeout(saveTimeout);
        saveTimeout = window.setTimeout(saveDraft, 200);
    }

    function removeDraft() {
        window.clearTimeout(saveTimeout);
        draftDisabled = true;
        try {
            sessionStorage.removeItem(storageKey);
        } catch (_) {
            // The page can still continue if session storage is disabled.
        }
    }

    function nameInitials() {
        if (nombreManual) return nombreManual.trim().slice(0, 1).toUpperCase();
        return (String(tokenizer[2] || '').slice(0, 1) + String(tokenizer[4] || '').slice(0, 1)).toUpperCase();
    }

    function renderVisitor() {
        const values = nombreManual ? [nombreManual, '', '', ''] : [
            tokenizer[2] || '', tokenizer[3] || '', tokenizer[4] || '', tokenizer[5] || ''
        ];
        ['1nombre', '2nombre', '1apellido', '2apellido'].forEach(function (id, index) {
            document.getElementById(id).textContent = values[index];
        });

        document.getElementById('iniciales').textContent = nameInitials();
        document.getElementById('icon').hidden = !nombreManual && tokenizer.length !== 11;
        document.getElementById('ocultar').hidden = !nombreManual && tokenizer.length === 11;
        document.getElementById('fechanac').textContent = nombreManual ? '' : (tokenizer[6] || '');
        document.getElementById('cui').textContent = nombreManual ? '' : (tokenizer[8] || '');
        document.getElementById('telefonoEmergencia').textContent = nombreManual ? '' : (tokenizer[9] || '');
        document.getElementById('tipo').textContent = nombreManual ? '' : (tokenizer[1] || '');
        document.getElementById('ndocument').textContent = nombreManual ? '' : (tokenizer[0] || '');
        document.getElementById('fechavec').textContent = nombreManual ? '' : (tokenizer[7] || '');
        document.getElementById('horaentrada').textContent = !nombreManual && tokenizer.length === 11 ? new Date().toLocaleString() : '';
        fields.manualName.value = nombreManual;
    }

    function setPhoto(role, dataUrl) {
        photos[role] = dataUrl || null;
        window[photoFields[role]] = photos[role] || undefined;
        const video = videoEls[role];
        const canvas = canvasEls[role];

        if (!photos[role]) {
            canvas.hidden = true;
            video.hidden = false;
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
            return Promise.resolve();
        }

        return new Promise(function (resolve) {
            const image = new Image();
            image.onload = function () {
                canvas.width = image.naturalWidth;
                canvas.height = image.naturalHeight;
                canvas.getContext('2d').drawImage(image, 0, 0);
                video.hidden = true;
                canvas.hidden = false;
                resolve();
            };
            image.onerror = function () {
                photos[role] = null;
                window[photoFields[role]] = undefined;
                video.hidden = false;
                canvas.hidden = true;
                resolve();
            };
            image.src = photos[role];
        });
    }

    async function restoreDraft() {
        let draft;
        try {
            draft = JSON.parse(sessionStorage.getItem(storageKey) || 'null');
        } catch (_) {
            try { sessionStorage.removeItem(storageKey); } catch (storageError) { /* Storage is disabled. */ }
            return;
        }

        if (!draft || draft.version !== 1 || String(draft.user_id) !== String(form.dataset.userId)) return;

        form.dataset.submissionId = typeof draft.submission_id === 'string' ? draft.submission_id : form.dataset.submissionId;
        fields.scanner.value = typeof draft.scanner === 'string' ? draft.scanner : '';
        fields.plate.value = typeof draft.plate === 'string' ? draft.plate : '';
        fields.cone.value = typeof draft.cone === 'string' ? draft.cone : '';
        fields.delivery.checked = draft.delivery === true;
        fields.resident.value = typeof draft.resident === 'string' ? draft.resident : '';
        if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
            window.jQuery(fields.resident).val(fields.resident.value).trigger('change.select2');
        }
        tokenizer = Array.isArray(draft.tokens) ? draft.tokens : [];
        nombreManual = typeof draft.manual_name === 'string' ? draft.manual_name : '';
        photos = Object.assign(photos, draft.photos || {});
        roles.forEach(function (role) {
            if (typeof photos[role] !== 'string' || !photos[role].startsWith('data:image/')) photos[role] = null;
            window[photoFields[role]] = photos[role] || undefined;
        });

        if (draft.pending_submission && typeof draft.pending_submission === 'object' && draft.attempt_sent === true) {
            pendingSubmission = draft.pending_submission;
            saveAttempted = true;
            lockedForRetry = true;
        }

        renderVisitor();
        if (typeof draft.manual_name_input === 'string') fields.manualName.value = draft.manual_name_input;
        await Promise.all(roles.map(role => setPhoto(role, photos[role])));
        if (fields.resident.value) loadResidentDetails(fields.resident.value);
        if (tokenizer.length || nombreManual || fields.plate.value) {
            displayMessage(lockedForRetry
                ? 'El último intento pudo haberse guardado. Reintente para confirmar el resultado; los datos quedarán bloqueados durante la verificación.'
                : 'Se recuperó el borrador de esta pestaña.', 'success');
        }
        updateSaveButton();
    }

    function splitScannerData(value) {
        let parts = String(value).replace(/[\]|]/g, '|').split('|');
        if (parts[0] && parts[0].length > 10) {
            parts[0] = parts[0].slice(-8);
            parts.splice(6, 1);
        }
        return parts;
    }

    function showManualDialog() {
        if (window.jQuery && window.jQuery.fn.modal) {
            window.jQuery('#modalimg').modal('show');
        } else {
            const modal = document.getElementById('modalimg');
            modal.classList.add('show');
            modal.style.display = 'block';
            modal.setAttribute('aria-hidden', 'false');
        }
    }

    function hideManualDialog() {
        if (window.jQuery && window.jQuery.fn.modal) {
            window.jQuery('#modalimg').modal('hide');
        } else {
            const modal = document.getElementById('modalimg');
            modal.classList.remove('show');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
        }
    }

    function clearVisitorDetails() {
        tokenizer = [];
        nombreManual = '';
        fields.manualName.value = '';
        ['iniciales', '1nombre', '2nombre', '1apellido', '2apellido', 'fechanac', 'cui',
            'telefonoEmergencia', 'tipo', 'ndocument', 'fechavec', 'horaentrada'].forEach(function (id) {
            document.getElementById(id).textContent = '';
        });
        document.getElementById('icon').hidden = true;
        document.getElementById('ocultar').hidden = false;
        roles.forEach(function (role) { setPhoto(role, null); });
        scheduleDraftSave();
    }

    function displayValidationErrors(result) {
        const errors = result && result.errors ? Object.keys(result.errors).reduce(function (messages, key) {
            const value = result.errors[key];
            return messages.concat(Array.isArray(value) ? value : [String(value)]);
        }, []) : [];
        displayMessage(result && result.message ? result.message : 'Revise los datos y vuelva a intentarlo.', 'error', errors);
    }

    function collectSubmission() {
        const manual = nombreManual.trim();
        const payload = {
            submission_id: form.dataset.submissionId,
            residente: fields.resident.value,
            placa: fields.plate.value.trim(),
            cono: fields.cone.value.trim(),
            tokens: tokenizer,
            es_repartidor: fields.delivery.checked ? '1' : '0',
            nombreManual: manual,
            photo1: photos.licencia,
            photo2: photos.placa,
            photo3: photos.visitante
        };
        const errors = [];

        if (!payload.residente) errors.push('Seleccione el número de casa.');
        if (!payload.placa) errors.push('Ingrese la placa del vehículo.');
        else if (payload.placa.length > 8) errors.push('La placa no puede superar los 8 caracteres.');
        if (!payload.cono) errors.push('Ingrese el número de cono.');
        else if (payload.cono.length > 20) errors.push('El número de cono no puede superar los 20 caracteres.');
        if (manual && manual.length > 30) errors.push('El nombre no puede superar los 30 caracteres.');
        if (!manual && tokenizer.length !== 11) errors.push('Escanee la licencia o complete el ingreso manual del visitante.');
        if (!manual && tokenizer.length === 11 && (!tokenizer[0] || !tokenizer[1] || !tokenizer[2] || !tokenizer[4] || !tokenizer[6] || !tokenizer[7] || !tokenizer[8])) {
            errors.push('La licencia no contiene todos los datos necesarios. Vuelva a escanearla.');
        }

        roles.forEach(function (role) {
            if (enabledPhotos[role] && !(role === 'licencia' && manual) && !payload[photoFields[role]]) {
                const labels = { licencia: 'licencia', placa: 'placa', visitante: 'visitante' };
                errors.push('Tome la foto de ' + labels[role] + ' antes de guardar.');
            }
        });

        return { payload: payload, errors: errors };
    }

    function prepareFormData(payload) {
        const data = new FormData();
        Object.keys(payload).forEach(function (key) {
            if (key === 'tokens') {
                if (Array.isArray(payload.tokens)) {
                    payload.tokens.forEach(function (token, index) { data.append('tokens[' + index + ']', token || ''); });
                }
            } else if (payload[key] !== null && payload[key] !== undefined) {
                data.append(key, String(payload[key]));
            }
        });
        return data;
    }

    // The budget includes headers AND the complete body, across the CSRF retry.
    async function fetchJsonWithTimeout(url, options, deadline, limitMs) {
        const remaining = Math.min(limitMs, deadline - Date.now());
        if (remaining <= 0) throw new DOMException('Tiempo de espera agotado', 'AbortError');
        const controller = new AbortController();
        let timer;
        try {
            return await Promise.race([
                (async function () {
                    const response = await fetch(url, Object.assign({}, options, { signal: controller.signal }));
                    const text = await response.text();
                    let result = null;
                    try {
                        result = JSON.parse(text);
                        if (typeof result === 'string') result = JSON.parse(result);
                    } catch (_) { /* Invalid JSON cannot confirm a registration. */ }
                    return { response: response, result: result };
                })(),
                new Promise(function (_, reject) {
                    timer = window.setTimeout(function () {
                        controller.abort();
                        reject(new DOMException('Tiempo de espera agotado', 'AbortError'));
                    }, remaining);
                })
            ]);
        } finally {
            window.clearTimeout(timer);
        }
    }

    async function getSessionState(deadline) {
        const reply = await fetchJsonWithTimeout(form.dataset.sessionUrl, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { Accept: 'application/json' }
        }, deadline, 10000);
        const response = reply.response;
        if (response.status === 401 || response.status === 403) {
            const error = new Error('session');
            error.sessionExpired = true;
            throw error;
        }
        if (!response.ok) throw new Error('session-check');
        const result = reply.result;
        if (!result || typeof result !== 'object') throw new Error('session-check');
        if (String(result.user_id) !== String(form.dataset.userId)) {
            const error = new Error('account-changed');
            error.accountChanged = true;
            throw error;
        }
        if (typeof result.csrf_token !== 'string' || !result.csrf_token) throw new Error('csrf-token');
        form.dataset.csrfToken = result.csrf_token;
        return result.csrf_token;
    }

    async function postSubmission(payload, csrfToken, deadline) {
        return fetchJsonWithTimeout(form.dataset.saveUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: prepareFormData(payload)
        }, deadline, 60000);
    }

    function completeRegistration(result) {
        const id = result && result.id;
        if (!(typeof id === 'number' || (typeof id === 'string' && /^\d+$/.test(id)))) return false;

        const url = result.redirect_url || form.dataset.detailTemplate.replace('__VISIT_ID__', encodeURIComponent(id));
        const target = new URL(url, window.location.href);
        if (target.origin !== window.location.origin || !target.pathname.includes('/visit/detailvisit/')) return false;

        registrationComplete = true;
        saving = false;
        updateSaveButton();
        displayMessage('La visita quedó registrada.', 'success');
        window.clearTimeout(alertTimer);
        const link = document.createElement('a');
        link.href = target.href;
        link.textContent = ' Abrir el detalle de la visita';
        messageBox.appendChild(link);
        removeDraft();
        window.location.replace(target.href);
        return true;
    }

    function setUncertainState(message) {
        saving = false;
        lockedForRetry = true;
        saveAttempted = true;
        updateSaveButton();
        displayMessage(message + ' Reintente para verificarlo; se usará el mismo envío y no se duplicará la visita.', 'error');
        saveDraft();
    }

    function resetSubmission() {
        lockedForRetry = false;
        pendingSubmission = null;
        saveAttempted = false;
    }

    async function submitForm(event) {
        event.preventDefault();
        if (saving || registrationComplete) return;
        const retryingUncertainSubmission = lockedForRetry;
        if (lockedForRetry && !pendingSubmission) {
            setUncertainState('No se puede recuperar el envío anterior. Revise el listado de visitas antes de iniciar otro registro.');
            return;
        }

        let payload = pendingSubmission;
        if (!lockedForRetry) {
            const collected = collectSubmission();
            if (collected.errors.length) {
                displayMessage('Revise estos datos antes de guardar:', 'error', collected.errors);
                messageBox.focus();
                return;
            }
            payload = collected.payload;
            pendingSubmission = payload;
            saveAttempted = false;
        }

        const deadline = Date.now() + 60000;
        let mayHaveSaved = retryingUncertainSubmission;
        saving = true;
        updateSaveButton();
        clearMessage();
        saveDraft();
        try {
            let csrfToken = await getSessionState(deadline);
            mayHaveSaved = saveAttempted = true;
            saveDraft();
            let reply = await postSubmission(payload, csrfToken, deadline);
            if (reply.response.status === 419) {
                // Only this rejected POST is known not to have saved. An older
                // uncertain attempt must remain protected during session renewal.
                mayHaveSaved = saveAttempted = retryingUncertainSubmission;
                saveDraft();
                csrfToken = await getSessionState(deadline);
                mayHaveSaved = saveAttempted = true;
                saveDraft();
                reply = await postSubmission(payload, csrfToken, deadline);
            }

            const response = reply.response;
            const result = reply.result;
            if (response.ok && completeRegistration(result)) return;
            if (response.ok) {
                setUncertainState('El servidor respondió, pero no confirmó el registro con una respuesta válida.');
                return;
            }

            const rejected = [401, 403, 413, 419, 422].includes(response.status);
            const notSaved = result && result.outcome === 'not_saved';
            if (retryingUncertainSubmission || (!rejected && !notSaved)) {
                setUncertainState(result && result.message ? result.message : 'El servidor no confirmó el resultado del guardado.');
                if ([401, 419].includes(response.status)) addLoginLink();
                return;
            }

            resetSubmission();
            if ([401, 403, 419].includes(response.status)) {
                displayMessage('Su sesión venció o ya no es válida. Inicie sesión en otra pestaña y luego vuelva aquí para guardar.', 'error');
                addLoginLink();
            } else if (response.status === 413) {
                displayMessage('Las fotos son demasiado grandes para enviarse juntas. Vuelva a tomarlas y trate de guardar nuevamente.', 'error');
            } else if (response.status === 422) {
                displayValidationErrors(result);
            } else {
                displayMessage(result.message || 'No se guardó la visita. Sus datos y fotos siguen en el formulario; vuelva a intentarlo.', 'error');
            }
        } catch (error) {
            if (mayHaveSaved) {
                setUncertainState(error.sessionExpired || error.accountChanged
                    ? 'La sesión cambió o venció y el guardado pendiente aún no se confirmó.'
                    : (error.name === 'AbortError'
                        ? 'El guardado agotó el límite de 60 segundos y aún no se puede confirmar el resultado.'
                        : 'Se interrumpió la conexión durante el guardado.'));
            } else {
                resetSubmission();
                displayMessage(error.accountChanged
                    ? 'La sesión pertenece a otra cuenta. Recupere la visita con la cuenta con que la comenzó.'
                    : (error.sessionExpired
                        ? 'Su sesión venció. Inicie sesión en otra pestaña y luego vuelva aquí para reintentar.'
                        : 'No se pudo verificar la sesión a tiempo. Revise la conexión; sus datos se conservaron y puede reintentar.'), 'error');
            }
            if (error.sessionExpired) addLoginLink();
        } finally {
            saving = false;
            updateSaveButton();
            saveDraft();
        }
    }

    function handleScannerKey(event) {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        if (saving || lockedForRetry) return;

        const scannedValue = fields.scanner.value;
        fields.scanner.value = '';
        clearVisitorDetails();
        tokenizer = splitScannerData(scannedValue);

        if (tokenizer.length === 11) {
            renderVisitor();
            clearMessage();
            scheduleDraftSave();
            return;
        }

        tokenizer = [];
        renderVisitor();
        displayMessage('No se pudo leer la licencia. Revise el código e intente escanearlo nuevamente o utilice el ingreso manual.', 'error');
        showManualDialog();
        scheduleDraftSave();
    }

    function handleManualSave() {
        if (saving || lockedForRetry) return;
        const manualName = fields.manualName.value.trim();
        if (!manualName) {
            fields.manualName.focus();
            displayMessage('Ingrese el nombre del visitante para continuar.', 'error');
            return;
        }

        tokenizer = [];
        nombreManual = manualName;
        roles.forEach(function (role) { setPhoto(role, null); });
        renderVisitor();
        hideManualDialog();
        clearMessage();
        scheduleDraftSave();
    }

    async function loadResidentDetails(id) {
        if (!id) {
            document.getElementById('ftelefono').value = '';
            document.getElementById('direccion').value = '';
            return;
        }

        try {
            const url = new URL(form.dataset.residentUrl, window.location.origin);
            url.searchParams.set('id', id);
            const response = await fetch(url.href, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('resident');
            let resident = await response.json();
            if (typeof resident === 'string') resident = JSON.parse(resident);
            document.getElementById('ftelefono').value = resident.Codigo || '';
            document.getElementById('direccion').value = resident.Direccion || '';
        } catch (_) {
            displayMessage('No se pudieron cargar los datos de la casa. Verifique su conexión; la visita sigue en el formulario.', 'error');
        }
    }

    function cameraControls() {
        const enabledRoles = roles.filter(function (role) { return enabledPhotos[role]; });
        const captureButton = document.getElementById('capture-btn');
        if (enabledRoles.length === 0) {
            captureButton.hidden = true;
            return;
        }

        const mediaDevices = navigator.mediaDevices;
        if (!mediaDevices || typeof mediaDevices.getUserMedia !== 'function' || typeof mediaDevices.enumerateDevices !== 'function') {
            displayMessage('Este navegador no permite abrir la cámara. Verifique los permisos o la conexión segura del sitio.', 'error');
            captureButton.disabled = true;
            return;
        }

        (async function () {
            let permissionStream;
            try {
                permissionStream = await mediaDevices.getUserMedia({ video: true, audio: false });
                permissionStream.getTracks().forEach(function (track) { track.stop(); });
                const devices = (await mediaDevices.enumerateDevices()).filter(function (device) { return device.kind === 'videoinput'; });
                const byId = Object.fromEntries(devices.map(function (device) { return [device.deviceId, device]; }));
                const byLabel = Object.fromEntries(devices.map(function (device) { return [device.label, device]; }));
                const wanted = {
                    licencia: form.dataset.cameraLicencia,
                    placa: form.dataset.cameraPlaca,
                    visitante: form.dataset.cameraVisitante
                };
                const used = new Set();
                const missing = [];

                for (const role of enabledRoles) {
                    let deviceId = wanted[role];
                    if (deviceId && byLabel[deviceId]) deviceId = byLabel[deviceId].deviceId;
                    if (!deviceId || !byId[deviceId] || used.has(deviceId)) {
                        const alternative = devices.find(function (device) { return !used.has(device.deviceId); });
                        deviceId = alternative ? alternative.deviceId : null;
                    }
                    if (!deviceId) {
                        missing.push(role);
                        continue;
                    }

                    used.add(deviceId);
                    try {
                        const stream = await mediaDevices.getUserMedia({ video: { deviceId: { exact: deviceId } }, audio: false });
                        const video = videoEls[role];
                        video.muted = true;
                        video.playsInline = true;
                        video.srcObject = stream;
                        cameras[role] = stream;
                        await video.play();
                        if (!video.videoWidth || !video.videoHeight) missing.push(role);
                    } catch (_) {
                        missing.push(role);
                    }
                }

                captureButton.disabled = !enabledRoles.some(function (role) { return cameras[role] && videoEls[role].videoWidth; });
                if (missing.length) {
                    displayMessage('No se pudo abrir la cámara de ' + missing.join(', ') + '. Revise los permisos y vuelva a cargar esta pantalla.', 'error');
                }
            } catch (_) {
                if (permissionStream) permissionStream.getTracks().forEach(function (track) { track.stop(); });
                captureButton.disabled = true;
                displayMessage('No se pudo solicitar acceso a las cámaras. Autorice las cámaras y vuelva a cargar la pantalla; los demás datos se conservarán.', 'error');
            }
        })();

        captureButton.addEventListener('click', function () {
            if (saving || lockedForRetry) return;
            const failures = [];
            enabledRoles.forEach(function (role) {
                if (role === 'licencia' && nombreManual) return;
                const video = videoEls[role];
                const canvas = canvasEls[role];
                if (!cameras[role] || !video.videoWidth || !video.videoHeight || video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) {
                    failures.push(role);
                    return;
                }

                try {
                    const scale = Math.min(1, 1280 / video.videoWidth, 960 / video.videoHeight);
                    canvas.width = Math.round(video.videoWidth * scale);
                    canvas.height = Math.round(video.videoHeight * scale);
                    const context = canvas.getContext('2d');
                    context.drawImage(video, 0, 0, canvas.width, canvas.height);
                    const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
                    if (!dataUrl || dataUrl === 'data:,') throw new Error('empty-frame');
                    photos[role] = dataUrl;
                    window[photoFields[role]] = dataUrl;
                    video.hidden = true;
                    canvas.hidden = false;
                } catch (_) {
                    failures.push(role);
                }
            });

            if (failures.length) {
                displayMessage('No se pudo capturar ' + failures.join(', ') + '. Revise las cámaras e intente nuevamente.', 'error');
            } else {
                displayMessage('Fotos capturadas y guardadas en el borrador de esta pestaña.', 'success');
            }
            scheduleDraftSave();
        });
    }

    form.addEventListener('submit', submitForm);
    fields.scanner.addEventListener('keydown', handleScannerKey);
    fields.resident.addEventListener('change', function () {
        loadResidentDetails(fields.resident.value);
        scheduleDraftSave();
    });
    document.getElementById('guardarManual').addEventListener('click', handleManualSave);
    document.getElementById('abrirIngresoManual').addEventListener('click', showManualDialog);
    mutableControls.forEach(function (control) {
        control.addEventListener('input', scheduleDraftSave);
        control.addEventListener('change', scheduleDraftSave);
    });

    if (cancelLink) {
        cancelLink.addEventListener('click', function (event) {
            if (saving) {
                event.preventDefault();
                return;
            }
            if (lockedForRetry && !window.confirm('Aún no se confirmó el último guardado. La visita podría existir; revise el listado antes de cancelarla. ¿Desea salir?')) {
                event.preventDefault();
                return;
            }
            removeDraft();
        });
    }

    window.addEventListener('pagehide', function () {
        saveDraft();
        Object.keys(cameras).forEach(function (role) {
            if (cameras[role]) cameras[role].getTracks().forEach(function (track) { track.stop(); });
        });
    });

    if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
        try {
            window.jQuery('#selectResidente').select2({ theme: 'bootstrap4', placeholder: 'Seleccione número de casa' });
        } catch (_) {
            displayMessage('La búsqueda de residentes no está disponible; puede elegir la casa de la lista.', 'error');
        }
    }

    restoreDraft();
    cameraControls();
    updateSaveButton();
}());
