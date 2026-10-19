/* Browser adapters only: Livewire owns visitor data, validation and registration. */
(function initialize() {
    'use strict';
    const root = document.querySelector('[data-vehicle-visit-component]');
    if (!root) return;
    const component = root.__livewire;
    if (!component) { document.addEventListener('livewire:load', initialize, {once:true}); return; }
    const form = document.getElementById('vehicle-visit-form');
    const transportAlert = document.getElementById('vehicle-transport-alert');
    const roles = ['licencia', 'placa', 'visitante'];
    const photoFields = {licencia:'photo1', placa:'photo2', visitante:'photo3'};
    const videoEls = Object.fromEntries(roles.map((role, i) => [role, document.getElementById('video'+(i+1))]));
    const canvasEls = Object.fromEntries(roles.map((role, i) => [role, document.getElementById('canvas'+(i+1))]));
    const cameras = Object.fromEntries(roles.map(role => [role, null]));
    const enabledRoles = roles.filter(role => form.dataset['photo'+role[0].toUpperCase()+role.slice(1)] === 'true');
    const storageKey = 'garita.vehicle-visit.v1.'+root.dataset.userId;
    let imageBackup = {}, backupDisabled = false, quotaReported = false, backupTimer;
    let originalBackup;
    try { originalBackup = JSON.parse(sessionStorage.getItem(storageKey) || 'null'); } catch (_) { /* Keep the current page usable. */ }
    let restoring = !!originalBackup;
    let inFlight = false, activeGeneration = 0, cameraInitializationComplete = false, leftPage = false;
    let transportRequest = null, revision = 0;
    const edits = new Map(), preservedEdits = new WeakMap(), requestRevisions = new WeakMap();

    function boundInputs() {
        return Array.from(root.querySelectorAll('input, select')).map(element => ({
            element, name: element.getAttribute('wire:model.defer') || element.getAttribute('wire:model') || element.dataset.livewireInput,
        })).filter(binding => binding.name);
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
                inputs:Object.fromEntries(boundInputs().map(({element,name}) => [name,inputValue(element)])),
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
    root.addEventListener('change', scheduleBackup);
    window.addEventListener('pagehide', () => {
        leftPage = true; backup();
        new Set(Object.values(cameras)).forEach(stream => stream?.getTracks().forEach(track => track.stop()));
    });
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

    function widgets() {
        const select = document.getElementById('selectResidente');
        if (window.jQuery?.fn.select2) {
            const jq = window.jQuery(select);
            if (!jq.data('select2')) {
                jq.select2({theme:'bootstrap4',placeholder:'Seleccione número de casa'});
                jq.on('change.vehicleVisit', () => {
                    edits.set('residente', ++revision);
                    // Only cancel a superseded selection binding, never an action
                    // that can register a visit or finish a file upload.
                    const message = component.messageInTransit;
                    if (message && message.updateQueue.every(action => action.type === 'syncInput')
                        && message.updateQueue.some(action => action.type === 'syncInput' && action.payload.name === 'residente')) {
                        if (transportRequest) { transportRequest.superseded = true; transportRequest.controller.abort(); }
                    }
                    component.sync('residente', select.value);
                    scheduleBackup();
                });
            }
            // A newer unsent selection must survive an earlier response.
            if (!component.messageInTransit && !component.updateQueue.length) jq.val(component.data.residente || '').trigger('change.select2');
            jq.prop('disabled', !!select.closest('fieldset')?.disabled);
        }
        const modal = document.getElementById('modalimg');
        if (window.jQuery?.fn.modal) {
            const instance = window.jQuery(modal).data('bs.modal');
            if (instance?._isTransitioning) return;
            window.jQuery(modal).modal(component.data.modalOpen ? 'show' : 'hide');
        }
    }
    if (window.jQuery) window.jQuery('#modalimg').on('shown.bs.modal hidden.bs.modal', widgets);
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
        widgets(); backup();
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
        const controller = new AbortController(), request = {controller,superseded:false};
        transportRequest = request;
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
            if (!request.superseded) { form.inert=true; transportAlert.hidden=false; }
            if (component.messageInTransit===message) component.messageSendFailed();
            if (component.updateQueue.length) setTimeout(() => component.fireMessage(),0);
        } finally {
            if (transportRequest===request) transportRequest=null;
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

    function dataUriFile(data, role) {
        const comma=data.indexOf(','), mime=data.slice(5,data.indexOf(';'));
        const decoded=atob(data.slice(comma+1)), bytes=new Uint8Array(decoded.length);
        for(let i=0;i<decoded.length;i++) bytes[i]=decoded.charCodeAt(i);
        return new File([bytes],role+'.'+(mime==='image/png'?'png':'jpg'),{type:mime});
    }
    function uploadPhoto(generation, role, data) {
        imageBackup[role]=data; window[photoFields[role]]=data; backup();
        component.upload('uploads.'+generation+'.'+role,dataUriFile(data,role),()=>{},
            () => component.call('cameraFailed',generation,[role]).catch(()=>{}),()=>{});
    }
    window.addEventListener('vehicle:reset-cameras', event => {
        activeGeneration=event.detail.generation;imageBackup={};
        roles.forEach(role => {window[photoFields[role]]=undefined;});backup();
    });
    window.addEventListener('vehicle:restore-photos', event => {
        activeGeneration=event.detail.generation;
        const source=event.detail.source==='pending_submission'?originalBackup?.pending_submission:originalBackup?.photos;
        const missing=[];
        for(const role of event.detail.roles) {
            const data=source?.[role] || source?.[photoFields[role]];
            if(typeof data==='string' && data.startsWith('data:image/')) uploadPhoto(activeGeneration,role,data);
            else missing.push(role);
        }
        if(missing.length) component.call('cameraFailed',activeGeneration,missing).catch(()=>{});
    });
    window.addEventListener('vehicle:capture', async event => {
        const {generation,roles:requested}=event.detail;
        activeGeneration=generation;
        const deadline=Date.now()+10000;
        while(!leftPage && activeGeneration===generation) {
            const ready=requested.every(role=>cameras[role] && videoEls[role].videoWidth && videoEls[role].videoHeight && videoEls[role].readyState>=HTMLMediaElement.HAVE_CURRENT_DATA);
            if(ready || Date.now()>=deadline || (cameraInitializationComplete && requested.some(role=>!cameras[role]))) break;
            await new Promise(resolve=>setTimeout(resolve,80));
        }
        if(leftPage || activeGeneration!==generation) return;
        const failed=[];
        for(const role of requested) {
            const video=videoEls[role],canvas=canvasEls[role];
            if(!cameras[role] || !video.videoWidth || video.readyState<HTMLMediaElement.HAVE_CURRENT_DATA){failed.push(role);continue;}
            try {
                const scale=Math.min(1,1280/video.videoWidth,960/video.videoHeight);
                canvas.width=Math.round(video.videoWidth*scale);canvas.height=Math.round(video.videoHeight*scale);
                canvas.getContext('2d').drawImage(video,0,0,canvas.width,canvas.height);
                const data=canvas.toDataURL('image/jpeg',0.85);
                if(!data || data==='data:,')throw new Error('empty-frame');
                uploadPhoto(generation,role,data);
            }catch(_){failed.push(role);}
        }
        if(failed.length)component.call('cameraFailed',generation,failed).catch(()=>{});
    });

    async function openCameras() {
        if(!enabledRoles.length){cameraInitializationComplete=true;return;}
        const media=navigator.mediaDevices;
        if(!media?.getUserMedia || !media.enumerateDevices){cameraInitializationComplete=true;return;}
        let permission;
        try {
            permission=await media.getUserMedia({video:true,audio:false});
            permission.getTracks().forEach(track=>track.stop());permission=null;
            const devices=(await media.enumerateDevices()).filter(device=>device.kind==='videoinput');
            const opened=new Map();
            for(const role of enabledRoles) {
                const wanted=form.dataset['camera'+role[0].toUpperCase()+role.slice(1)];
                const device=devices.find(item=>item.deviceId===wanted || item.label===wanted)
                    || devices.find(item=>!opened.has(item.deviceId)) || devices[0];
                if(!device)continue;
                try {
                    if(!opened.has(device.deviceId))opened.set(device.deviceId,media.getUserMedia({video:{deviceId:{exact:device.deviceId}},audio:false}));
                    const stream=await opened.get(device.deviceId);
                    if(leftPage){stream.getTracks().forEach(track=>track.stop());continue;}
                    cameras[role]=stream;videoEls[role].srcObject=stream;
                    await videoEls[role].play();
                }catch(_){/* The capture event reports unavailable roles to PHP. */}
            }
        }catch(_){permission?.getTracks().forEach(track=>track.stop());}
        finally{cameraInitializationComplete=true;}
    }
    function backupMetadata(value) {
        return JSON.parse(JSON.stringify(value,(_,item)=>typeof item==='string'&&item.startsWith('data:image/')?'browser-file':item));
    }
    widgets();openCameras();
    if(originalBackup) {
        // Image bytes use native uploads, not component properties or a JSON send.
        const metadata=backupMetadata(originalBackup);
        component.call('restoreDraft',metadata).catch(()=>{});
    } else backup();
}());
