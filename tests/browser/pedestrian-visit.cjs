// Run against the isolated test server described in README.md, after the MySQL suite.
const assert = require('node:assert/strict');
const { chromium, webkit } = require(process.env.PLAYWRIGHT_MODULE || 'playwright-core');
const base = process.env.VISITS_BASE_URL || 'http://127.0.0.1:18880';
if (!['localhost', '127.0.0.1', 'garita-visits-web-test'].includes(new URL(base).hostname)) throw new Error('Use a local isolated test server.');

async function fakeCamera() {
    window.dpiTest = { opened: 0, stopped: 0, requests: [], failEnumerate: false };
    const enumerate = async () => {
        if (window.dpiTest.failEnumerate) throw new Error('Device list unavailable');
        return [
            { kind: 'videoinput', label: 'Cámara frontal de prueba', deviceId: 'fake-front' },
            { kind: 'videoinput', label: 'Cámara trasera de prueba', deviceId: 'fake-rear' }
        ];
    };
    const mediaDevices = { enumerateDevices: enumerate };
    try { Object.defineProperty(navigator, 'mediaDevices', { configurable: true, value: mediaDevices }); }
    catch (_) {
        try { Object.defineProperty(navigator.mediaDevices, 'enumerateDevices', { configurable: true, value: enumerate }); }
        catch (_) { navigator.mediaDevices.enumerateDevices = enumerate; }
    }
    navigator.mediaDevices.getUserMedia = async constraints => {
        window.dpiTest.requests.push(constraints);
        const requested = constraints.video && constraints.video.deviceId && constraints.video.deviceId.exact;
        if (requested === 'missing-camera') throw new DOMException('Missing device', 'OverconstrainedError');
        const activeId = requested || (constraints.video && constraints.video.facingMode ? 'fake-rear' : 'fake-front');
        const canvas = document.createElement('canvas');
        canvas.width = 1920; canvas.height = 1080;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#234'; ctx.fillRect(0, 0, 1920, 1080);
        // Match the document guide in the real capture code.
        const height = 1080 * 0.85, width = height * 1.586;
        ctx.translate((1920 - width) / 2, (1080 - height) / 2);
        ctx.scale(width / 1600, height / 1009);
        ctx.fillStyle = '#fafcf7'; ctx.fillRect(0, 0, 1600, 1009);
        ctx.fillStyle = '#111';
        function text(value, x, y, size = 42) { ctx.font = size + 'px Arial'; ctx.fillText(value, x, y); }
        text('DOCUMENTO DE PRUEBA', 120, 110, 44);
        text('CUI', 80, 245, 28); text('1234 56789 0101', 70, 325, 44);
        text('NOMBRE / GIVEN NAMES', 565, 245, 23);
        text('MARIA', 565, 300); text('LUCIA', 565, 350);
        text('APELLIDOS / SURNAME', 565, 390, 23);
        text('PEREZ', 565, 440); text('LOPEZ', 565, 490);
        text('NACIONALIDAD', 565, 575, 23); text('GTM', 565, 630);
        text('SEXO', 565, 680, 23);
        text('FECHA DE NACIMIENTO', 565, 745, 23);
        text('09AGO1990', 565, 810);
        const stream = canvas.captureStream(5);
        const track = stream.getVideoTracks()[0];
        const original = track.stop.bind(track);
        track.stop = () => { window.dpiTest.stopped++; original(); };
        track.getSettings = () => ({ deviceId: activeId, width: 1920, height: 1080 });
        window.dpiTest.opened++;
        return stream;
    };
}

async function sampleDpi(page) {
    return page.evaluate(() => {
        const canvas = document.createElement('canvas');
        canvas.width = 1600; canvas.height = 1009;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#fafcf7'; ctx.fillRect(0, 0, 1600, 1009);
        ctx.fillStyle = '#111';
        function text(value, x, y, size = 42) { ctx.font = size + 'px Arial'; ctx.fillText(value, x, y); }
        text('DOCUMENTO DE PRUEBA', 120, 110, 44);
        text('CUI', 80, 245, 28); text('1234 56789 0101', 70, 325, 44);
        text('NOMBRE / GIVEN NAMES', 565, 245, 23); text('MARIA', 565, 300); text('LUCIA', 565, 350);
        text('APELLIDOS / SURNAME', 565, 390, 23); text('PEREZ', 565, 440); text('LOPEZ', 565, 490);
        text('FECHA DE NACIMIENTO', 565, 745, 23); text('09AGO1990', 565, 810);
        const portrait = document.createElement('canvas');
        portrait.width = 1009; portrait.height = 1600;
        const rotated = portrait.getContext('2d');
        rotated.translate(portrait.width, 0); rotated.rotate(Math.PI / 2); rotated.drawImage(canvas, 0, 0);
        return portrait.toDataURL('image/png').split(',')[1];
    });
}

async function waitForDpiRead(page) {
    for (let attempt = 0; attempt < 600; attempt++) {
        const status = await page.locator('#dpi-status').textContent();
        if (status.includes('Lectura terminada') || status.includes('Lectura parcial') || status.includes('La lectura no pudo completarse')) return status;
        await page.waitForTimeout(100);
    }
    throw new Error('DPI OCR did not finish: ' + await page.locator('#dpi-status').textContent());
}

(async () => {
    const engine = process.env.VISITS_BROWSER === 'webkit' ? webkit : chromium;
    const browser = await engine.launch({ headless: true });
    try {
        const context = await browser.newContext({ viewport: { width: 1366, height: 1000 } });
        const page = await context.newPage();
        const errors = [], externalOcr = [];
        page.on('pageerror', error => {
            // Scope the assertion to visit pages; the existing login bundle is unrelated.
            if (new URL(page.url()).pathname.startsWith('/visit/')) errors.push({ url: page.url(), stack: error.stack });
        });
        page.on('request', request => {
            if (/tesseract|traineddata/.test(request.url()) && !request.url().startsWith(base) && !request.url().startsWith('blob:')) externalOcr.push(request.url());
        });
        await context.route('**/*', route => {
            const url = route.request().url();
            return url.startsWith(base) || url.startsWith('blob:') || url.startsWith('data:') ? route.continue() : route.abort();
        });
        await page.goto(base + '/login', { waitUntil: 'domcontentloaded' });
        await page.fill('[name=username]', process.env.VISITS_TEST_USERNAME || 'admin');
        await page.fill('[name=password]', process.env.VISITS_TEST_PASSWORD || 'admin123');
        await Promise.all([page.waitForURL(url => !url.pathname.includes('login')), page.click('button[type=submit]')]);
        const webkitRun = process.env.VISITS_BROWSER === 'webkit';
        if (!webkitRun) await context.addInitScript(fakeCamera);
        await context.addInitScript(() => {
            if (new URL(location.href).searchParams.has('insecure')) {
                Object.defineProperty(window, 'isSecureContext', { configurable: true, value: false });
            }
        });
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto(base + '/visit/pedestrian/create' + (webkitRun ? '?insecure=1' : ''), { waitUntil: 'domcontentloaded' });
        if (webkitRun) {
            assert.equal(await page.evaluate(() => window.isSecureContext), false);
            assert.equal(await page.locator('#dpi-start').isVisible(), false);
            assert.equal(await page.locator('#dpi-native-start').isVisible(), true);
            const sample = await sampleDpi(page);
            await page.locator('#dpi-native-file').setInputFiles({ name: 'dpi-prueba-girada.png', mimeType: 'image/png', buffer: Buffer.from(sample, 'base64') });
            await page.waitForFunction(() => !document.querySelector('#dpi-use-crop').disabled);
            await page.click('#dpi-rotate-left');
            await page.click('#dpi-use-crop');
            await waitForDpiRead(page);
            assert.equal(await page.inputValue('#cui'), '1234567890101');
            assert.equal(await page.inputValue('#Primer_Nombre'), 'MARIA');
            assert.equal(await page.inputValue('#Primer_Apellido'), 'PEREZ');
            assert.equal(await page.inputValue('#Fecha_nac'), '1990-08-09');
            for (const viewport of [{ width: 390, height: 844 }, { width: 820, height: 1180 }, { width: 1024, height: 768 }]) {
                await page.setViewportSize(viewport);
                assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth), 'WebKit layout has no horizontal overflow');
            }
            await page.selectOption('#pedestrian-resident', '1');
            await page.click('#dpi-save');
            await page.waitForURL('**/visit/detailvisit/*');
            assert.match(await page.locator('body').innerText(), /Visita peatonal/);
            assert.equal(await page.getByAltText('Foto del DPI').count(), 1);
            assert.deepEqual(externalOcr, []);
            assert.deepEqual(errors, []);
            console.log('PASS: WebKit mobile HTTP capture, rotation, crop, local OCR, save and responsive layouts');
            return;
        }
        await page.evaluate(() => {
            window.dpiOcrResults = [];
            const create = Tesseract.createWorker;
            Tesseract.createWorker = async function (...args) {
                const worker = await create(...args);
                const recognize = worker.recognize.bind(worker);
                worker.recognize = async function (...parameters) {
                    const result = await recognize(...parameters);
                    window.dpiOcrResults.push(result.data.text);
                    return result;
                };
                return worker;
            };
        });
        assert.equal(await page.locator('#dpi-native-start').isVisible(), true);
        assert.equal(await page.locator('#dpi-camera').isVisible(), false);
        await page.evaluate(() => localStorage.setItem('garita-dpi-camera-1', 'missing-camera'));
        await page.click('#dpi-start');
        await page.waitForFunction(() => !document.querySelector('#dpi-capture').disabled);
        assert.equal(await page.locator('video').count(), 1);
        assert.equal(await page.locator('#dpi-camera').isVisible(), true, JSON.stringify(await page.evaluate(() => ({ hidden: document.querySelector('#dpi-camera').hidden, options: Array.from(document.querySelector('#dpi-camera').options).map(option => option.value), status: document.querySelector('#dpi-status').textContent }))));
        assert.equal(await page.evaluate(() => localStorage.getItem('garita-dpi-camera-1')), 'fake-rear');
        assert.equal(await page.evaluate(() => window.dpiTest.requests[0].video.deviceId.exact), 'missing-camera');
        assert.equal(await page.evaluate(() => window.dpiTest.requests[1].video.facingMode.ideal), 'environment');
        await page.selectOption('#dpi-camera', 'fake-front');
        await page.waitForFunction(() => window.dpiTest.opened === 2 && !document.querySelector('#dpi-capture').disabled);
        assert.equal(await page.evaluate(() => window.dpiTest.stopped), 1);
        assert.equal(await page.evaluate(() => localStorage.getItem('garita-dpi-camera-1')), 'fake-front');
        await page.evaluate(() => { window.dpiTest.failEnumerate = true; });
        const [liveCameraChooser] = await Promise.all([page.waitForEvent('filechooser'), page.click('#dpi-native-start')]);
        await liveCameraChooser.setFiles([]);
        assert.equal(await page.evaluate(() => window.dpiTest.stopped), 2, 'opening the native camera releases the live stream');
        assert.equal(await page.locator('#dpi-capture').isDisabled(), true);
        await page.click('#dpi-start');
        await page.waitForFunction(() => window.dpiTest.opened === 3 && !document.querySelector('#dpi-capture').disabled);
        assert.equal(await page.locator('#dpi-camera').isVisible(), false);
        assert.equal(await page.evaluate(() => window.dpiTest.stopped), 2);
        await page.evaluate(() => { window.dpiTest.failEnumerate = false; });
        await page.setViewportSize({ width: 844, height: 390 });
        await page.waitForTimeout(300);
        assert.ok(await page.evaluate(() => {
            const video = document.querySelector('#dpi-video').getBoundingClientRect();
            const guide = document.querySelector('#dpi-frame').getBoundingClientRect();
            return guide.width > 0 && guide.height > 0 && guide.left >= video.left && guide.right <= video.right && guide.top >= video.top && guide.bottom <= video.bottom;
        }), 'camera guide stays aligned in landscape orientation');
        await page.setViewportSize({ width: 390, height: 844 });
        await page.waitForTimeout(300);
        await page.click('#dpi-capture');
        await page.waitForFunction(() => !document.querySelector('#dpi-save').disabled, null, { timeout: 60000 });
        assert.equal(await page.inputValue('#cui'), '1234567890101');
        assert.equal(await page.inputValue('#Fecha_nac'), '1990-08-09', JSON.stringify(await page.evaluate(() => window.dpiOcrResults)));
        assert.equal(await page.inputValue('#Primer_Nombre'), 'MARIA');
        assert.equal(await page.inputValue('#Primer_Apellido'), 'PEREZ');
        assert.equal(await page.evaluate(() => window.dpiTest.stopped), 3, 'live capture releases its camera track');
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth), 'phone layout has no horizontal overflow');
        for (const viewport of [{ width: 820, height: 1180 }, { width: 1024, height: 768 }]) {
            await page.setViewportSize(viewport);
            const layout = await page.evaluate(() => {
                const columns = Array.from(document.querySelectorAll('.col-xl-6')).map(element => element.getBoundingClientRect());
                return { width: document.documentElement.scrollWidth, client: document.documentElement.clientWidth, captureTop: columns[0].top, visitorTop: columns[1].top };
            });
            assert.ok(layout.width <= layout.client, 'tablet layout has no horizontal overflow');
            assert.ok(layout.visitorTop >= layout.captureTop, 'tablet form keeps capture before the visitor fields');
        }
        await page.setViewportSize({ width: 390, height: 844 });
        console.log('PASS: real local OCR fills the document fields');

        await page.click('#dpi-retake');
        const [chooser] = await Promise.all([page.waitForEvent('filechooser'), page.click('#dpi-native-start')]);
        await chooser.setFiles([]);
        await page.click('#dpi-back-review');
        assert.equal(await page.locator('#dpi-review').isVisible(), true);
        assert.equal(await page.locator('#dpi-save').isDisabled(), false);
        assert.equal(await page.inputValue('#cui'), '1234567890101');
        console.log('PASS: cancelling the native camera picker preserves the current photo and fields');

        await page.fill('#Primer_Nombre', 'CORREGIDO');
        await page.click('#dpi-retake');
        await page.click('#dpi-start');
        await page.waitForFunction(() => window.dpiTest.opened === 4 && !document.querySelector('#dpi-capture').disabled);
        await page.click('#dpi-capture');
        await page.waitForFunction(() => !document.querySelector('#dpi-save').disabled, null, { timeout: 60000 });
        assert.equal(await page.inputValue('#Primer_Nombre'), 'MARIA');
        assert.equal(await page.evaluate(() => window.dpiTest.opened), 4);
        await page.selectOption('#pedestrian-resident', '1');
        assert.equal(await page.inputValue('#pedestrian-address'), 'Casa A-12');
        console.log('PASS: repeated capture uses one camera and resident selection fills the address');
        if (process.env.VISITS_SCREENSHOT) await page.screenshot({ path: process.env.VISITS_SCREENSHOT, fullPage: true });

        // Failure must preserve the captured document and editable fields for a retry.
        await page.route('**/visit/pedestrian/save', route => route.fulfill({ status: 503, contentType: 'application/json', body: JSON.stringify({ message: 'Fallo simulado' }) }), { times: 1 });
        await page.fill('#Primer_Nombre', 'CORREGIDO');
        await page.click('#dpi-save');
        await page.waitForFunction(() => !document.querySelector('#dpi-errors').hidden);
        assert.equal(await page.inputValue('#Primer_Nombre'), 'CORREGIDO');
        await Promise.all([page.waitForURL('**/visit/detailvisit/*'), page.click('#dpi-save')]);
        assert.match(await page.locator('body').innerText(), /CORREGIDO/);
        assert.equal(await page.getByAltText('Foto del DPI').count(), 1);
        console.log('PASS: save failure retains data; retry stores the corrected visit and DPI');

        await page.goto(base + '/visit/pedestrian/create?insecure=1', { waitUntil: 'domcontentloaded' });
        assert.equal(await page.evaluate(() => window.isSecureContext), false);
        assert.equal(await page.locator('#dpi-start').isVisible(), false);
        assert.equal(await page.locator('#dpi-native-start').isVisible(), true);
        assert.match(await page.locator('#dpi-status').innerText(), /HTTP|cámara en vivo/);
        const sample = await sampleDpi(page);
        await page.locator('#dpi-native-file').setInputFiles({ name: 'dpi-prueba.png', mimeType: 'image/png', buffer: Buffer.from(sample, 'base64') });
        await page.waitForFunction(() => !document.querySelector('#dpi-crop-panel').hidden && !document.querySelector('#dpi-use-crop').disabled);
        await page.click('#dpi-rotate-left');
        await page.click('#dpi-use-crop');
        await page.waitForFunction(() => !document.querySelector('#dpi-save').disabled, null, { timeout: 60000 });
        assert.equal(await page.evaluate(() => window.dpiTest.opened), 0, 'HTTP fallback does not request the live camera');
        assert.equal(await page.inputValue('#cui'), '1234567890101');
        assert.equal(await page.inputValue('#Primer_Nombre'), 'MARIA');
        assert.equal(await page.inputValue('#Primer_Apellido'), 'PEREZ');
        assert.equal(await page.inputValue('#Fecha_nac'), '1990-08-09');
        assert.ok(await page.locator('#dpi-preview').evaluate(image => image.naturalWidth <= 1600));
        await page.selectOption('#pedestrian-resident', '1');
        const nativeValidity = await page.locator('#pedestrian-form').evaluate(form => ({ valid: form.checkValidity(), values: Object.fromEntries(['Primer_Nombre', 'Primer_Apellido', 'Fecha_nac', 'cui'].map(id => [id, document.getElementById(id).value])) }));
        assert.equal(nativeValidity.valid, true);
        const nativeResponse = page.waitForResponse(response => response.url().includes('/visit/pedestrian/save') && response.request().method() === 'POST');
        await page.click('#dpi-save');
        const savedNative = await nativeResponse;
        if (savedNative.status() !== 201) throw new Error('Native photo save failed (' + savedNative.status() + '): ' + await savedNative.text());
        await page.waitForURL('**/visit/detailvisit/*');
        assert.match(await page.locator('body').innerText(), /Visita peatonal/);
        assert.equal(await page.getByAltText('Foto del DPI').count(), 1);
        console.log('PASS: HTTP native photo can be rotated, cropped, read locally, and does not request getUserMedia');

        await page.goto(base + '/visit/pedestrian/create', { waitUntil: 'domcontentloaded' });
        await page.evaluate(() => { Tesseract.createWorker = async () => { throw new Error('Simulated OCR failure'); }; });
        await page.click('#dpi-start');
        await page.waitForFunction(() => !document.querySelector('#dpi-capture').disabled);
        await page.click('#dpi-capture');
        await page.waitForFunction(() => !document.querySelector('#dpi-save').disabled);
        assert.match(await page.locator('#dpi-status').innerText(), /manualmente/);
        assert.equal(await page.locator('#dpi-preview').isVisible(), true);
        assert.equal(await page.inputValue('#cui'), '');
        console.log('PASS: OCR failure preserves the photo and allows manual entry');

        await page.goto(base + '/visit/create', { waitUntil: 'domcontentloaded' });
        assert.equal(await page.isChecked('#es_repartidor'), false);
        await page.click('.switch label');
        await page.fill('#placa', 'P123ABC');
        await page.fill('#cono', '17');
        await page.selectOption('#selectResidente', '1');
        const code = ['12345678', 'C', 'JUAN', '', 'PEREZ', '', '01/01/1990', '01/01/2030', '1234567890101', '12345678', ''].join('|');
        await page.fill('#data', code);
        await page.press('#data', 'Enter');
        await Promise.all([page.waitForURL('**/visit/detailvisit/*'), page.click('#guardar')]);
        assert.match(await page.locator('body').innerText(), /JUAN/);
        assert.match(await page.locator('body').innerText(), /Repartidor\s+Sí/);

        await page.goto(base + '/visit/create', { waitUntil: 'domcontentloaded' });
        await page.click('.switch label');
        await page.fill('#placa', 'P124ABC');
        await page.fill('#cono', '18');
        await page.selectOption('#selectResidente', '1');
        await page.click('#abrirIngresoManual');
        assert.equal(await page.locator('#modalimg').isVisible(), true);
        await page.click('#modalimg [data-dismiss="modal"]');
        await page.waitForFunction(() => !document.querySelector('#modalimg').classList.contains('show'));
        await page.click('#abrirIngresoManual');
        await page.click('#guardarManual');
        await page.locator('.swal2-popup').waitFor();
        assert.equal(await page.locator('#modalimg').isVisible(), true);
        await page.click('.swal2-confirm');
        await page.fill('#nombre-manual', 'MANUAL');
        await page.click('#guardarManual');
        assert.equal(await page.locator('#1nombre').innerText(), 'MANUAL');
        await Promise.all([page.waitForURL('**/visit/detailvisit/*'), page.click('#guardar')]);
        assert.match(await page.locator('body').innerText(), /MANUAL/);
        assert.match(await page.locator('body').innerText(), /Repartidor\s+Sí/);
        console.log('PASS: vehicle switch persists for scanned and manually entered visitors');

        const today = new Date().toISOString().slice(0, 10);
        await page.goto(base + '/visit/table/' + today + '/' + today + '?tipo_visita=vehicular&es_repartidor=1', { waitUntil: 'domcontentloaded' });
        await page.waitForFunction(() => !!document.querySelector('.buttons-excel'));
        const exported = await page.evaluate(() => $('#visitreport').DataTable().buttons.exportData({ columns: ':not(:first-child)' }));
        assert.ok(exported.body.length > 0);
        assert.ok(exported.body.every(row => row[exported.header.indexOf('Tipo de visita')] === 'Vehicular' && row[exported.header.indexOf('Repartidor')] === 'Sí'));
        async function download(selector, signature, filename) {
            const [file] = await Promise.all([page.waitForEvent('download'), page.click(selector)]);
            const stream = await file.createReadStream();
            const chunks = [];
            for await (const chunk of stream) chunks.push(chunk);
            const bytes = Buffer.concat(chunks);
            assert.equal(bytes.subarray(0, signature.length).toString(), signature);
            if (process.env.VISITS_EXPORT_DIR) await file.saveAs(require('node:path').join(process.env.VISITS_EXPORT_DIR, filename));
        }
        await download('.buttons-excel', 'PK', 'garita-visits.xlsx');
        await download('.buttons-pdf', '%PDF', 'garita-visits-browser.pdf');
        await download('a:has-text("Descargar PDF")', '%PDF', 'garita-visits-server.pdf');
        await page.goto(base + '/visit/tablebyresident/' + today + '/' + today + '/1?tipo_visita=peatonal&es_repartidor=0', { waitUntil: 'domcontentloaded' });
        await page.waitForFunction(() => !!document.querySelector('.buttons-excel'));
        const byResident = await page.evaluate(() => $('#visitreport_byresident').DataTable().buttons.exportData({ columns: ':not(:first-child)' }));
        assert.ok(byResident.body.length > 0);
        assert.ok(byResident.body.every(row => row[byResident.header.indexOf('Tipo de visita')] === 'Peatonal' && row[byResident.header.indexOf('Repartidor')] === 'No'));
        await download('.buttons-pdf', '%PDF', 'garita-visits-resident.pdf');
        console.log('PASS: report filters apply to date/resident tables, Excel, browser PDF and server PDF');

        await page.goto(base + '/visit/pedestrian/create', { waitUntil: 'domcontentloaded' });
        await page.evaluate(() => { navigator.mediaDevices.getUserMedia = async () => { throw new DOMException('Denied', 'NotAllowedError'); }; });
        await page.click('#dpi-start');
        await page.waitForFunction(() => document.querySelector('#dpi-status').textContent.includes('permiso'));
        assert.equal(await page.locator('#dpi-save').isDisabled(), true);
        assert.equal(await page.locator('#dpi-native-start').isVisible(), true);
        console.log('PASS: denied camera permission leaves the native camera alternative available');
        assert.deepEqual(externalOcr, []);
        assert.deepEqual(errors, []);
        console.log('PASS: no uncaught page errors or external OCR requests');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
