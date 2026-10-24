/* Browser camera adapter; registration state and validation stay in Livewire. */
(function () {
    'use strict';

    function initialize(root, component) {
        const form = root.querySelector('#vehicle-visit-form');
        const roles = ['licencia', 'placa', 'visitante'];
        const photoFields = {licencia:'photo1', placa:'photo2', visitante:'photo3'};
        const videoEls = Object.fromEntries(roles.map((role, i) => [role, document.getElementById('video'+(i+1))]));
        const canvasEls = Object.fromEntries(roles.map((role, i) => [role, document.getElementById('canvas'+(i+1))]));
        const cameras = Object.fromEntries(roles.map(role => [role, null]));
        const enabledRoles = roles.filter(role => form.dataset['photo'+role[0].toUpperCase()+role.slice(1)] === 'true');
        let activeGeneration = 0, cameraInitializationComplete = false, cameraOpening = false, leftPage = false;

        function dataUriFile(data, role) {
            const comma = data.indexOf(','), mime = data.slice(5, data.indexOf(';'));
            const decoded = atob(data.slice(comma+1)), bytes = new Uint8Array(decoded.length);
            for (let i=0; i<decoded.length; i++) bytes[i] = decoded.charCodeAt(i);
            return new File([bytes], role+'.'+(mime === 'image/png' ? 'png' : 'jpg'), {type:mime});
        }

        function uploadPhoto(generation, role, data) {
            window[photoFields[role]] = data;
            component.upload('uploads.'+generation+'.'+role, dataUriFile(data, role), () => {},
                () => component.call('cameraFailed', generation, [role]).catch(() => {}), () => {});
        }

        window.addEventListener('vehicle:reset-cameras', event => {
            activeGeneration = event.detail.generation;
            roles.forEach(role => { window[photoFields[role]] = undefined; });
        });

        window.addEventListener('vehicle:capture', async event => {
            const {generation, roles:requested} = event.detail;
            activeGeneration = generation;
            const deadline = Date.now()+10000;
            while (!leftPage && activeGeneration === generation) {
                const ready = requested.every(role => cameras[role] && videoEls[role].videoWidth && videoEls[role].videoHeight
                    && videoEls[role].readyState >= HTMLMediaElement.HAVE_CURRENT_DATA);
                if (ready || Date.now() >= deadline || (cameraInitializationComplete && requested.some(role => !cameras[role]))) break;
                await new Promise(resolve => setTimeout(resolve, 80));
            }
            if (leftPage || activeGeneration !== generation) return;
            const failed = [];
            for (const role of requested) {
                const video = videoEls[role], canvas = canvasEls[role];
                if (!cameras[role] || !video.videoWidth || video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) {
                    failed.push(role);
                    continue;
                }
                try {
                    const scale = Math.min(1, 1280/video.videoWidth, 960/video.videoHeight);
                    canvas.width = Math.round(video.videoWidth*scale);
                    canvas.height = Math.round(video.videoHeight*scale);
                    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                    const data = canvas.toDataURL('image/jpeg', 0.85);
                    if (!data || data === 'data:,') throw new Error('empty-frame');
                    uploadPhoto(generation, role, data);
                } catch (_) { failed.push(role); }
            }
            if (failed.length) component.call('cameraFailed', generation, failed).catch(() => {});
        });

        window.addEventListener('pagehide', () => {
            leftPage = true;
            new Set(Object.values(cameras)).forEach(stream => stream?.getTracks().forEach(track => track.stop()));
        });

        function resumeCameras() {
            leftPage = false;
            const needsRestart = enabledRoles.some(role => cameras[role]
                && cameras[role].getTracks().some(track => track.readyState === 'ended'));
            if (!needsRestart) return;
            new Set(Object.values(cameras)).forEach(stream => stream?.getTracks().forEach(track => track.stop()));
            roles.forEach(role => { cameras[role] = null; videoEls[role].srcObject = null; });
            cameraInitializationComplete = false;
            openCameras();
        }

        window.addEventListener('vehicle:resume-cameras', resumeCameras);
        window.addEventListener('pageshow', resumeCameras);

        async function openCameras() {
            if (!enabledRoles.length) { cameraInitializationComplete = true; return; }
            const media = navigator.mediaDevices;
            if (!media?.getUserMedia || !media.enumerateDevices) { cameraInitializationComplete = true; return; }
            if (cameraOpening) return;
            cameraOpening = true;
            let permission;
            try {
                permission = await media.getUserMedia({video:true, audio:false});
                permission.getTracks().forEach(track => track.stop());
                permission = null;
                const devices = (await media.enumerateDevices()).filter(device => device.kind === 'videoinput');
                const opened = new Map(), assignments = new Map();
                for (const role of enabledRoles) {
                    const wanted = form.dataset['camera'+role[0].toUpperCase()+role.slice(1)];
                    // Device IDs change between origins; keep repeated saved
                    // assignments shared even when their original ID is missing.
                    const device = assignments.get(wanted)
                        || devices.find(item => item.deviceId === wanted || item.label === wanted)
                        || devices.find(item => !opened.has(item.deviceId)) || devices[0];
                    if (!device) continue;
                    if (wanted) assignments.set(wanted, device);
                    try {
                        if (!opened.has(device.deviceId)) {
                            opened.set(device.deviceId, media.getUserMedia({video:{deviceId:{exact:device.deviceId}}, audio:false}));
                        }
                        const stream = await opened.get(device.deviceId);
                        if (leftPage) { stream.getTracks().forEach(track => track.stop()); continue; }
                        cameras[role] = stream;
                        videoEls[role].srcObject = stream;
                        await videoEls[role].play();
                    } catch (_) { /* PHP reports requested roles when capture is unavailable. */ }
                }
            } catch (_) { permission?.getTracks().forEach(track => track.stop()); }
            finally { cameraInitializationComplete = true; cameraOpening = false; }
        }

        openCameras();
    }

    window.VehicleVisitCamera = {initialize};
}());
