/* Browser adapters only: Livewire owns visitor data, validation and registration. */
(function initialize() {
    'use strict';
    const root = document.querySelector('[data-vehicle-visit-component]');
    if (!root) return;
    const component = root.__livewire;
    if (!component) { document.addEventListener('livewire:load', initialize, {once:true}); return; }
    const form = document.getElementById('vehicle-visit-form');
    // Drop data saved by older versions; every page starts a new registration.
    try { sessionStorage.removeItem('garita.vehicle-visit.v1.'+root.dataset.userId); } catch (_) {}
    let revision = 0;
    let verifyingSubmission = false;
    let scannerFocusPending = true;
    let activeTransportController = null;
    let sessionCheck = null;
    let systemLogRequest = null;
    const pendingSystemLogs = new Map();
    const reportedTransportIssues = new Set();
    const retryWaiters = new Set();
    const edits = new Map(), preservedEdits = new WeakMap(), requestRevisions = new WeakMap();

    function wakeRetryWaiters() {
        Array.from(retryWaiters).forEach(wake => wake());
    }
    function waitBeforeRetry(delay) {
        return new Promise(resolve => {
            let timer;
            const finish = () => {
                clearTimeout(timer);
                retryWaiters.delete(finish);
                window.removeEventListener('online',finish);
                window.removeEventListener('focus',finish);
                window.removeEventListener('pageshow',finish);
                document.removeEventListener('visibilitychange',finish);
                resolve();
            };
            retryWaiters.add(finish);
            window.addEventListener('online',finish);
            window.addEventListener('focus',finish);
            window.addEventListener('pageshow',finish);
            document.addEventListener('visibilitychange',finish);
            if (navigator.onLine) timer=setTimeout(finish,delay);
        });
    }

    function transportFailureKind(error) {
        if (error?.code === 'upload-url-expired') return 'upload-url-expired';
        if (error?.code === 'account-changed') return 'account-changed';
        if (error?.code === 'authentication-required') return 'authentication-required';
        if (!navigator.onLine) return 'offline';
        if (error?.name === 'AbortError') return 'timeout';
        return 'request-failed';
    }

    function flushSystemLogs(token) {
        if (systemLogRequest || !token || !pendingSystemLogs.size) return systemLogRequest;
        const batch = Array.from(pendingSystemLogs.entries());
        systemLogRequest = fetch(form.dataset.sessionUrl, {
            method:'POST',credentials:'same-origin',keepalive:true,
            headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':token},
            body:JSON.stringify({events:batch.map(([,event])=>event)}),
        }).then(response => {
            if (response.status !== 204) return;
            batch.forEach(([key])=>pendingSystemLogs.delete(key));
            if (pendingSystemLogs.size) queueMicrotask(()=>flushSystemLogs(token));
        }).catch(() => {}).finally(() => { systemLogRequest = null; });
        return systemLogRequest;
    }

    function logTransportIssue(operation, error, sendToServer = true) {
        const event = transportFailureKind(error);
        const key = operation+':'+event;
        if (!reportedTransportIssues.has(key)) {
            reportedTransportIssues.add(key);
            console.warn('[vehicle-visit] Fallo de transporte; reintento automático.', {operation,event});
            if (sendToServer) {
                pendingSystemLogs.set(key,{operation,event});
                flushSystemLogs(window.livewire_token);
            }
        }
    }

    function logTransportRecovery(operation) {
        const keys = Array.from(reportedTransportIssues).filter(key=>key.startsWith(operation+':'));
        if (!keys.length) return;
        console.info('[vehicle-visit] Conexión restablecida; operación recuperada.', {operation});
        keys.forEach(key=>reportedTransportIssues.delete(key));
    }

    function sessionActivity() {
        wakeRetryWaiters();
        window.dispatchEvent(new Event('vehicle:resume-cameras'));
        if (!navigator.onLine) return;
        refreshCsrf(Date.now()+10000).then(() => {
            logTransportRecovery('session-check');
        }).catch(error => {
            if (!component.messageInTransit) logTransportIssue('session-check',error);
        });
    }

    function scheduleScannerFocus() {
        requestAnimationFrame(() => {
            if (!scannerFocusPending || component.data.status !== 'editing'
                || component.data.modalOpen || form.inert || component.messageInTransit || component.updateQueue.length) return;
            const input = document.getElementById('data');
            if (!input || input.disabled || !input.getClientRects().length) return;
            input.focus({preventScroll:true});
            if (input.value) input.select();
            if (document.activeElement === input) scannerFocusPending = false;
        });
    }
    // Finish initial focus after Livewire initializes, without taking it
    // back once the operator chooses another control.
    root.addEventListener('pointerdown', () => { scannerFocusPending = false; }, true);
    root.addEventListener('keydown', event => {
        if (event.key === 'Tab') scannerFocusPending = false;
    });
    window.addEventListener('pageshow', sessionActivity);
    window.addEventListener('focus', sessionActivity);
    window.addEventListener('online', sessionActivity);
    window.addEventListener('offline', () => {
        if (!component.messageInTransit) return;
        pendingTransport = true;
        form.inert = true;
        activeTransportController?.abort();
    });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) sessionActivity(); });
    window.setInterval(() => {
        if (navigator.onLine) sessionActivity();
    }, 60000);

    const manualModal = window.jQuery('#modalimg');
    manualModal.modal({backdrop:'static', keyboard:true, show:false});
    function syncManualModal() {
        const instance = manualModal.data('bs.modal');
        if (instance?._isTransitioning) return;
        if (Boolean(instance?._isShown) !== Boolean(component.data.modalOpen)) {
            manualModal.modal(component.data.modalOpen ? 'show' : 'hide');
        }
    }
    manualModal.on('hide.bs.modal.vehicleVisit', () => {
        if (component.data.modalOpen) component.call('closeManual').catch(() => {});
    });
    manualModal.on('shown.bs.modal.vehicleVisit', () => {
        syncManualModal();
        if (component.data.modalOpen) document.getElementById('nombre-manual')?.focus();
    });
    manualModal.on('hidden.bs.modal.vehicleVisit', () => {
        syncManualModal();
        if (!component.data.modalOpen) { scannerFocusPending = true; scheduleScannerFocus(); }
    });

    function syncResidentSelect() {
        const select = document.getElementById('selectResidente');
        const widget = window.jQuery(select);
        if (!widget.data('select2')) {
            widget.select2({theme:'bootstrap4', width:'100%', placeholder:'Buscar acceso, casa o nombre', allowClear:true,
                matcher:(params, data)=>{
                    const normalize=value=>String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toUpperCase();
                    const terms=normalize(params.term).trim().split(/\s+/);
                    return terms.every(term=>normalize(data.text).includes(term)) ? data : null;
                },
                language:{noResults:()=>'No se encontraron residentes activos.', searching:()=>'Buscando…'}});
            widget.on('change.vehicleVisit', () => {
                edits.set('residente', ++revision);
                component.sync('residente', select.value);
            });
        }
        if (!component.messageInTransit && !component.updateQueue.length) {
            const selected=String(component.data.residente || '');
            if (widget.val() !== selected) widget.val(selected).trigger('change.select2');
        }
        const disabled=Boolean(select.closest('fieldset')?.disabled);
        if (select.disabled !== disabled) widget.prop('disabled', disabled);
    }

    function boundInputs() {
        return Array.from(root.querySelectorAll('input, select')).map(element => {
            const model = Array.from(element.attributes).find(attribute =>
                attribute.name === 'wire:model' || attribute.name.startsWith('wire:model.'));
            return {element, name:model?.value || element.dataset.livewireInput};
        }).filter(binding => binding.name);
    }
    function inputValue(element) { return element.type === 'checkbox' ? element.checked : element.value; }
    function writeValue(element, value) {
        if (element.type === 'checkbox') element.checked = !!value;
        else element.value = value == null ? '' : value;
    }
    root.addEventListener('input', event => {
        if (event.target.matches('input, select')) {
            const binding = boundInputs().find(item => item.element === event.target);
            if (binding) edits.set(binding.name, ++revision);
        }
    });
    root.addEventListener('change', event => {
        const binding = boundInputs().find(item => item.element === event.target);
        if (binding) edits.set(binding.name, ++revision);
    });
    window.Livewire.hook('message.received', (message, owner) => {
        if (owner !== component) return;
        const sent = requestRevisions.get(message) || 0;
        preservedEdits.set(message, boundInputs().filter(({name}) => (edits.get(name) || 0) > sent)
            .map(({element,name}) => ({name,value:inputValue(element)})));
    });
    window.Livewire.hook('message.processed', (message, owner) => {
        if (owner !== component) return;
        for (const {name,value} of preservedEdits.get(message) || []) {
            const binding = boundInputs().find(item => item.name === name);
            if (binding) { writeValue(binding.element,value); component.set(name,value,true); }
        }
        preservedEdits.delete(message);
        if (component.data.status === 'editing') verifyingSubmission = false;
        syncResidentSelect();
        syncManualModal();
        scheduleScannerFocus();
    });

    // Isolated transport guard. It forwards the unmodified native Livewire
    // envelope; it never constructs a visitor payload or interprets save results.
    async function fetchJson(url, options, deadline, limit, externalSignal) {
        const controller = new AbortController();
        const abort = () => controller.abort();
        if (externalSignal) {
            if (externalSignal.aborted) abort();
            else externalSignal.addEventListener('abort',abort,{once:true});
        }
        const remaining = Math.min(limit, deadline-Date.now());
        if (remaining <= 0) throw new DOMException('Tiempo de espera agotado','AbortError');
        let timer;
        try {
            return await Promise.race([
                (async () => {
                    const response = await fetch(url,{...options,signal:controller.signal});
                    const text = await response.text();
                    let data; try { data=JSON.parse(text); } catch (_) { data=null; }
                    return {response,data};
                })(),
                new Promise((_, reject) => { timer=setTimeout(() => {abort();reject(new DOMException('Tiempo de espera agotado','AbortError'));},remaining); }),
            ]);
        } finally {
            clearTimeout(timer);
            externalSignal?.removeEventListener('abort',abort);
        }
    }
    function refreshCsrf(deadline, externalSignal) {
        if (sessionCheck) return sessionCheck;
        const check = (async () => {
            const {response,data} = await fetchJson(form.dataset.sessionUrl, {
                credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'},
            },deadline,10000,externalSignal);
            const contentType = response.headers.get('content-type') || '';
            if (response.status === 401 || response.status === 403 || response.redirected || contentType.includes('text/html')) {
                const error = new Error('authentication-required');
                error.code = 'authentication-required';
                throw error;
            }
            if (!response.ok || !data || typeof data.csrf_token !== 'string' || data.user_id == null) {
                throw new Error('session-unavailable');
            }
            window.livewire_token = data.csrf_token;
            if (String(data.user_id) !== String(root.dataset.userId)) {
                const error = new Error('account-changed');
                error.code = 'account-changed';
                throw error;
            }
            flushSystemLogs(data.csrf_token);
            return data.csrf_token;
        })();
        sessionCheck = check.finally(() => { sessionCheck = null; });
        return sessionCheck;
    }
    const connection = Object.create(component.connection);
    component.connection = connection;
    connection.sendMessage = async function (message) {
        const controller = new AbortController();
        requestRevisions.set(message,revision);
        const longRequest = message.updateQueue.some(action => ['save','recover'].includes(action.payload?.method || action.method));
        if (message.updateQueue.some(action => (action.payload?.method || action.method) === 'save')) verifyingSubmission = true;
        const operation = longRequest ? 'save' : 'livewire-action';
        const body = JSON.stringify(message.payload());
        activeTransportController = controller;
        const retryDelays = [1000,2000,5000,10000,30000];
        let attempt = 0;
        while (component.messageInTransit === message) {
            if (!navigator.onLine) {
                form.inert = true;
                logTransportIssue(operation,new Error('offline'));
                await waitBeforeRetry(1000);
            }
            const deadline = Date.now()+(longRequest?60000:10000);
            try {
                let token = await refreshCsrf(deadline,controller.signal), reply;
                for (let csrfAttempt=0; csrfAttempt<2; csrfAttempt++) {
                    reply = await fetchJson(form.dataset.livewireUrl, {
                        method:'POST',credentials:'same-origin',body,
                        headers:{Accept:'application/json','Content-Type':'application/json','X-Livewire':'true','X-CSRF-TOKEN':token},
                    },deadline,longRequest?60000:10000,controller.signal);
                    if (reply.response.status!==419 || csrfAttempt===1) break;
                    token = await refreshCsrf(deadline,controller.signal);
                }
                const contentType = reply.response.headers.get('content-type') || '';
                if (reply.response.status === 401 || reply.response.redirected || contentType.includes('text/html')) {
                    const error = new Error('authentication-required');
                    error.code = 'authentication-required';
                    throw error;
                }
                if (!reply.response.ok || !reply.data?.serverMemo || !Array.isArray(reply.data.effects?.dirty)
                    || typeof reply.data.serverMemo.checksum !== 'string') throw new Error('unconfirmed-response');
                activeTransportController = null;
                form.inert = false;
                logTransportRecovery(operation);
                connection.onMessage(message,reply.data);
                scheduleScannerFocus();
                return;
            } catch (error) {
                activeTransportController = null;
                form.inert = true;
                if (error.code === 'account-changed') {
                    logTransportIssue(operation,error);
                    return;
                }
                if (error.code === 'authentication-required') {
                    logTransportIssue(operation,error);
                    await waitBeforeRetry(60000);
                } else {
                    logTransportIssue(operation,error);
                    await waitBeforeRetry(retryDelays[Math.min(attempt,retryDelays.length-1)]);
                    attempt++;
                }
            }
        }
    };
    // Livewire 2's upload XHR has no timeout/error listener. Preserve its upload
    // protocol and progress callbacks while bounding the request for this form.
    component.uploadManager.makeRequest = async function (name, body, method, url, headers, retrievePaths) {
        const manager = this;
        const retryDelays = [1000,2000,5000,10000,30000];
        let attempt = 0;
        for (;;) {
            if (!navigator.onLine) await waitBeforeRetry(1000);
            const deadline = Date.now()+60000;
            try {
                const token = await refreshCsrf(deadline);
                const reply = await new Promise((resolve,reject) => {
                    const xhr=new XMLHttpRequest();xhr.open(method,url);
                    xhr.timeout=Math.max(1,deadline-Date.now());
                    Object.entries({...headers,'X-CSRF-TOKEN':token}).forEach(([key,value]) => xhr.setRequestHeader(key,value));
                    xhr.upload.addEventListener('progress',event => {
                        event.detail={progress:Math.round(event.loaded*100/event.total)};
                        manager.uploadBag.first(name).progressCallback(event);
                    });
                    xhr.onload=() => {let data;try{data=JSON.parse(xhr.responseText);}catch(_){data=null;}resolve({status:xhr.status,data});};
                    xhr.onerror=xhr.ontimeout=xhr.onabort=() => reject(new Error('upload-network'));
                    xhr.send(body);
                });
                if (reply.status === 401 || reply.status === 403) {
                    // Livewire upload URLs expire after five minutes. Ask Livewire
                    // for a fresh signed URL and resend the same File from its upload bag.
                    logTransportIssue('photo-upload',Object.assign(new Error('upload-url-expired'),{code:'upload-url-expired'}));
                    manager.startUpload(name,manager.uploadBag.first(name));
                    return;
                }
                if (reply.status === 419) throw new Error('upload-csrf-expired');
                if (reply.status === 422) {
                    component.call('uploadErrored',name,reply.data,manager.uploadBag.first(name).multiple).catch(() => {});
                    return;
                }
                if (reply.status<200 || reply.status>=300 || !reply.data) throw new Error('upload-response');
                const paths=retrievePaths(reply.data);
                if (!Array.isArray(paths)) throw new Error('upload-response');
                logTransportRecovery('photo-upload');
                component.call('finishUpload',name,paths,manager.uploadBag.first(name).multiple).catch(() => {});
                return;
            } catch (error) {
                logTransportIssue('photo-upload',error);
                await waitBeforeRetry(retryDelays[Math.min(attempt,retryDelays.length-1)]);
                attempt++;
            }
        }
    };

    window.VehicleVisitCamera.initialize(root, component);
    syncResidentSelect();
    syncManualModal();
    scheduleScannerFocus();
}());
