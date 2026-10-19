// Real form and saves, synthetic cameras, isolated MySQL/local image storage only.
const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright-core');
const base = 'http://localhost:18880';
const code = ['12345678','C','JUAN','','PEREZ','','01/01/1990','01/01/2030','1234567890101','12345678',''].join('|');
async function fakeCamera(settings) {
    window.cameraTest = { calls: [], stopped: {} };
    const devices = settings.devices.map(id => ({ kind: 'videoinput', deviceId: id, label: 'Label ' + id }));
    Object.defineProperty(navigator, 'mediaDevices', { configurable: true, value: {
        enumerateDevices: async () => devices,
        getUserMedia: async constraints => {
            const id = constraints.video?.deviceId?.exact || 'permission';
            window.cameraTest.calls.push(id);
            if (settings.deny && id !== 'permission') throw new DOMException('Denied', 'NotAllowedError');
            if (settings.delay && id !== 'permission') await new Promise(resolve=>setTimeout(resolve,settings.delay));
            const canvas = document.createElement('canvas'); canvas.width = 640; canvas.height = 480;
            const ctx = canvas.getContext('2d'); ctx.fillStyle = '#eee'; ctx.fillRect(0,0,640,480);
            ctx.fillStyle = '#123'; ctx.font = '30px Arial'; ctx.fillText('CAMARA SINTETICA ' + id,20,220);
            const stream = canvas.captureStream(5);
            const timer = setInterval(() => ctx.fillRect(0,0,5,5),100);
            const track = stream.getVideoTracks()[0]; const stop = track.stop.bind(track);
            track.stop = () => { clearInterval(timer); window.cameraTest.stopped[id] = (window.cameraTest.stopped[id] || 0) + 1; stop(); };
            return stream;
        }
    }});
}
(async () => {
    for (const channel of ['msedge', 'chrome']) {
        const browser = await chromium.launch({ channel, headless: true });
        try {
            const cases = [
                { name: 'one camera assigned three times', devices: ['shared'], cameras: ['shared','shared','shared'], groups: [0,0,0] },
                { name: 'one camera selected by label', devices: ['shared'], cameras: ['Label shared','Label shared','Label shared'], groups: [0,0,0] },
                { name: 'one available camera replaces unavailable IDs', devices: ['shared'], cameras: ['old1','old2','old3'], groups: [0,0,0] },
                { name: 'two cameras with one shared assignment', devices: ['shared','other'], cameras: ['shared','shared','other'], groups: [0,0,1] },
                { name: 'three distinct cameras', devices: ['a','b','c'], cameras: ['a','b','c'], groups: [0,1,2] },
                { name: 'automatic capture waits for camera startup', devices: ['shared'], cameras: ['shared','shared','shared'], groups: [0,0,0], delay: 2000 },
                { name: 'shared camera fails only one open attempt', devices: ['shared'], cameras: ['shared','shared','shared'], deny: true }
            ];
            for (const [index, settings] of cases.entries()) {
                const context = await browser.newContext({ viewport: { width: 1366, height: 1000 } });
                await context.addInitScript(fakeCamera, settings);
                await context.route('**/*', route => route.request().url().startsWith(base) ? route.continue() : route.abort());
                const page = await context.newPage(); const errors = []; page.on('pageerror', e => { if (page.url().includes('/visit/')) errors.push(e.message); });
                try {
                    const control = async data => {
                        const response = await context.request.post(base + '/_visits_test_control', { headers: { 'X-Visits-Test': 'isolated-visits-test' }, data });
                        assert.equal(response.status(),200); const state = await response.json();
                        assert.equal(state.database, 'garita_visits_test'); assert.equal(state.storage,'local'); return state;
                    };
                    const before = await control({ photos: [1,1,1], cameras: settings.cameras });
                    await page.goto(base + '/login'); await page.fill('[name=username]','admin'); await page.fill('[name=password]','visits-test-only');
                    await Promise.all([page.waitForURL(url => !url.pathname.includes('/login')), page.click('button[type=submit]')]);
                    await page.goto(base + '/visit/create');
                    await page.fill('#placa', (channel === 'chrome' ? 'SC' : 'SE') + 'C' + index); await page.fill('#cono','1'); await page.selectOption('#selectResidente','1');
                    await page.fill('#data',code); await page.press('#data','Enter');
                    if (settings.delay) assert.equal(await page.evaluate(()=>!!window.photo1),false,'scan happens before camera is ready');
                    if (settings.deny) {
                        await page.getByText(/No se pudo capturar licencia, placa, visitante/).waitFor();
                        const calls = await page.evaluate(() => window.cameraTest.calls.filter(id => id !== 'permission'));
                        assert.deepEqual(calls,['shared']);
                        await page.click('#guardar'); await page.getByText('Revise estos datos antes de guardar:').waitFor();
                        assert.equal(await page.locator('#guardar').isEnabled(),true);
                        assert.equal((await control({})).visits.length,before.visits.length);
                    } else {
                        await page.waitForFunction(() => [1,2,3].every(n => { const v = document.querySelector('#video'+n); return v.videoWidth > 0 && !v.paused; }));
                        const preview = await page.evaluate(() => {
                            const videos = [1,2,3].map(n => document.querySelector('#video'+n));
                            return { calls: window.cameraTest.calls.filter(id => id !== 'permission'), same: videos.map(a => videos.map(b => a.srcObject === b.srcObject)) };
                        });
                        assert.equal(preview.calls.length,new Set(settings.groups).size);
                        for (let i=0;i<3;i++) for (let j=0;j<3;j++) assert.equal(preview.same[i][j], settings.groups[i] === settings.groups[j]);
                        await page.waitForFunction(()=>[window.photo1,window.photo2,window.photo3].every(photo=>photo?.startsWith('data:image/jpeg;base64,')));
                        await page.waitForFunction(()=>{const c=document.querySelector('[data-vehicle-visit-component]').__livewire;return Object.keys(c.data.photoVersions).length===3&&!c.messageInTransit&&!c.updateQueue.length;});
                        const beforeRender=await page.evaluate(()=>({streams:[1,2,3].map(n=>document.querySelector('#video'+n).srcObject),photos:[photo1,photo2,photo3]}).photos);
                        await page.evaluate(()=>document.querySelector('[data-vehicle-visit-component]').__livewire.call('$refresh'));
                        assert.deepEqual(await page.evaluate(()=>[photo1,photo2,photo3]),beforeRender);
                        assert.equal(await page.locator('img[id^=photo-preview-]').count(),3);
                        assert.equal(await page.evaluate(()=>[1,2,3].every(n=>document.querySelector('#canvas'+n).hidden)),true);
                        assert.deepEqual(await page.evaluate(()=>window.cameraTest.calls.filter(id=>id!=='permission')),preview.calls);
                        assert.equal(await page.evaluate(() => [photo1,photo2,photo3].every(photo => photo.startsWith('data:image/jpeg;base64,'))),true);
                        await page.evaluate(() => window.dispatchEvent(new Event('pagehide')));
                        const stopped = await page.evaluate(() => window.cameraTest.stopped);
                        for (const id of preview.calls) assert.equal(stopped[id],1,'stop each shared stream once');
                        await Promise.all([page.waitForURL('**/visit/detailvisit/*'),page.click('#guardar')]);
                        const after = await control({}); assert.equal(after.visits.length,before.visits.length+1); assert.equal(after.visit_logs,before.visit_logs+1);
                        const visit = after.visits.find(v => Number(v.id) === Number(page.url().split('/').pop())); assert.ok(visit);
                        for (const path of [visit.path_licencia,visit.path_placa,visit.path_visitante]) {
                            assert.ok(path); const image = await context.request.get(base + '/visit/imagen?filename=' + encodeURIComponent(path));
                            assert.equal(image.status(),200); assert.match(image.headers()['content-type'],/^image\/jpeg/);
                        }
                    }
                    assert.deepEqual(errors,[]);
                    console.log('PASS '+channel+': '+settings.name);
                } finally { await context.close(); }
            }
        } finally { await browser.close(); }
    }
})().catch(error => { console.error(error.stack); process.exitCode = 1; });
