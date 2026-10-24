/* Browser adapters only: Livewire owns visitor data, validation and registration. */
(function initialize() {
    'use strict';
    const root = document.querySelector('[data-vehicle-visit-component]');
    if (!root) return;
    const component = root.__livewire;
    if (!component) { document.addEventListener('livewire:load', initialize, {once:true}); return; }
    const form = document.getElementById('vehicle-visit-form');
    const transportAlert = document.getElementById('vehicle-transport-alert');
    const storageKey = 'garita.vehicle-visit.v1.'+root.dataset.userId;
    let imageBackup = {}, backupDisabled = false, quotaReported = false, backupTimer;
    let originalBackup;
    try { originalBackup = JSON.parse(sessionStorage.getItem(storageKey) || 'null'); } catch (_) { /* Keep the current page usable. */ }
    let restoring = !!originalBackup;
    let inFlight = false, revision = 0;
    const edits = new Map(), preservedEdits = new WeakMap(), requestRevisions = new WeakMap();

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
    manualModal.on('hidden.bs.modal.vehicleVisit', syncManualModal);

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
                scheduleBackup();
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
    function backup() {
        if (backupDisabled || restoring) return;
        try {
            sessionStorage.setItem(storageKey, JSON.stringify({
                version:2, user_id:root.dataset.userId, submission_id:component.data.submissionId,
                token:component.data.draftToken,
                inputs:Object.fromEntries(boundInputs().filter(({element}) => !element.hasAttribute('data-no-backup'))
                    .map(({element,name}) => [name,inputValue(element)])),
                photos:imageBackup, in_flight:inFlight,
            }));
        } catch (_) {
            if (!quotaReported) {
                quotaReported = true;
                setTimeout(() => component.call('backupUnavailable').catch(() => {}), 0);
            }
        }
    }
    function scheduleBackup() { clearTimeout(backupTimer); backupTimer = setTimeout(backup, 200); }
    root.addEventListener('input', event => {
        if (event.target.matches('input, select')) {
            const binding = boundInputs().find(item => item.element === event.target);
            if (binding) edits.set(binding.name, ++revision);
            scheduleBackup();
        }
    });
    root.addEventListener('change', event => {
        const binding = boundInputs().find(item => item.element === event.target);
        if (binding) edits.set(binding.name, ++revision);
        scheduleBackup();
    });
    window.addEventListener('pagehide', () => {
        backup();
    });
    window.addEventListener('vehicle:reset-cameras', () => { imageBackup = {}; backup(); });
    window.addEventListener('vehicle:clear-backup', () => {
        backupDisabled = true; clearTimeout(backupTimer);
        try { sessionStorage.removeItem(storageKey); } catch (_) { /* Server already confirmed the outcome. */ }
    });
    window.addEventListener('vehicle:recovered', () => {
        restoring = false; form.inert = false; transportAlert.hidden = true; backup();
    });
    document.getElementById('vehicle-reconnect').addEventListener('click', () => {
        backup();
        try {
            originalBackup=JSON.parse(sessionStorage.getItem(storageKey));
            if(originalBackup) {
                restoring=true;
                component.call('restoreDraft',backupMetadata(originalBackup)).catch(()=>{});
                return;
            }
        } catch (_) { /* A blocked local store still permits server recovery. */ }
        component.call('recover').catch(()=>{});
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
        syncResidentSelect();
        syncManualModal();
        backup();
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
    async function refreshCsrf(deadline) {
        const {response,data} = await fetchJson(form.dataset.sessionUrl, {
            credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'},
        },deadline,10000);
        if (!response.ok || !data || typeof data.csrf_token !== 'string') throw new Error('session-unavailable');
        window.livewire_token = data.csrf_token;
        return data.csrf_token;
    }
    const connection = Object.create(component.connection);
    component.connection = connection;
    connection.sendMessage = async function (message) {
        const controller = new AbortController();
        requestRevisions.set(message,revision);
        const longRequest = message.updateQueue.some(action => ['save','recover','restoreDraft'].includes(action.method));
        const deadline = Date.now()+(longRequest?60000:10000);
        const body = JSON.stringify(message.payload());
        inFlight = true; backup();
        try {
            let token = longRequest ? await refreshCsrf(deadline) : window.livewire_token;
            let reply;
            for (let attempt=0;attempt<2;attempt++) {
                reply = await fetchJson(form.dataset.livewireUrl, {
                    method:'POST',credentials:'same-origin',body,
                    headers:{Accept:'application/json','Content-Type':'application/json','X-Livewire':'true','X-CSRF-TOKEN':token},
                },deadline,longRequest?60000:10000,controller.signal);
                if (reply.response.status!==419 || attempt===1) break;
                token = await refreshCsrf(deadline);
            }
            if (!reply.response.ok || !reply.data?.serverMemo || !Array.isArray(reply.data.effects?.dirty)
                || typeof reply.data.serverMemo.checksum !== 'string') throw new Error('unconfirmed-response');
            connection.onMessage(message,reply.data);
        } catch (_) {
            form.inert=true; transportAlert.hidden=false;
            if (component.messageInTransit===message) component.messageSendFailed();
            if (component.updateQueue.length) setTimeout(() => component.fireMessage(),0);
        } finally {
            inFlight=false; backup();
        }
    };
    // Livewire 2's upload XHR has no timeout/error listener. Preserve its upload
    // protocol and progress callbacks while bounding the request for this form.
    component.uploadManager.makeRequest = async function (name, body, method, url, headers, retrievePaths) {
        const deadline = Date.now()+60000;
        try {
            let token = await refreshCsrf(deadline), reply;
            for (let attempt=0;attempt<2;attempt++) {
                reply = await new Promise((resolve,reject) => {
                    const xhr=new XMLHttpRequest();xhr.open(method,url);
                    xhr.timeout=Math.max(1,deadline-Date.now());
                    Object.entries({...headers,'X-CSRF-TOKEN':token}).forEach(([key,value]) => xhr.setRequestHeader(key,value));
                    xhr.upload.addEventListener('progress',event => {
                        event.detail={progress:Math.round(event.loaded*100/event.total)};
                        this.uploadBag.first(name).progressCallback(event);
                    });
                    xhr.onload=() => {let data;try{data=JSON.parse(xhr.responseText);}catch(_){data=null;}resolve({status:xhr.status,data});};
                    xhr.onerror=xhr.ontimeout=xhr.onabort=() => reject(new Error('upload-network'));
                    xhr.send(body);
                });
                if (reply.status!==419 || attempt===1) break;
                token=await refreshCsrf(deadline);
            }
            if (reply.status<200 || reply.status>=300 || !reply.data) throw new Error('upload-response');
            const paths=retrievePaths(reply.data);
            if (!Array.isArray(paths)) throw new Error('upload-response');
            component.call('finishUpload',name,paths,this.uploadBag.first(name).multiple).catch(() => {});
        } catch (_) {component.call('uploadErrored',name,null,this.uploadBag.first(name).multiple).catch(() => {});}
    };

    function backupMetadata(value) {
        return JSON.parse(JSON.stringify(value,(_,item)=>typeof item==='string'&&item.startsWith('data:image/')?'browser-file':item));
    }
    window.VehicleVisitCamera.initialize(root, component, {
        originalBackup: () => originalBackup,
        recordPhoto: (role, data) => { imageBackup[role] = data; backup(); },
    });
    if(originalBackup) {
        // Image bytes use native uploads, not component properties or a JSON send.
        const metadata=backupMetadata(originalBackup);
        component.call('restoreDraft',metadata).catch(()=>{});
    } else backup();
    syncResidentSelect();
    syncManualModal();
}());
