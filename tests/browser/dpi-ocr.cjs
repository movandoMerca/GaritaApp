// Standalone OCR/UI regression suite. No Laravel server, database or real identity documents.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const http = require('node:http');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright-core');
const root = path.resolve(__dirname, '../..');
const fieldNames = ['Primer_Nombre', 'Segundo_Nombre', 'Primer_Apellido', 'Segundo_Apellido', 'cui', 'Fecha_nac'];

function harness() {
    const view = fs.readFileSync(path.join(root, 'resources/views/visits/pedestrian.blade.php'), 'utf8');
    let form = view.match(/<form[\s\S]*<\/form>/)[0];
    form = form.replace(/@foreach \(\['Primer_Nombre'[\s\S]*?@endforeach/, () => {
        const template = view.slice(view.indexOf('<div class="form-group col-md-6">'), view.indexOf('@endforeach'));
        return fieldNames.slice(0, 4).map((field, i) => template
            .replace(/{{ \$field }}/g, field)
            .replace(/{{ \$definition\[0\] }}/g, field)
            .replace(/{{ \$definition\[1\] }}/g, [30, 70, 45, 70][i])
            .replace(/@if\(in_array[\s\S]*?@endif/, i === 0 || i === 2 ? 'required' : '')).join('');
    });
    form = form.replace(/@foreach \(\$residents[\s\S]*?@endforeach/, '<option value="1" data-address="Casa de prueba">Residente de prueba</option>');
    form = form.replace(/{{ route\('save.pedestrian.visits'\) }}/g, '/save')
        .replace(/{{ route\('home'\) }}/g, '/').replace(/{{ auth\(\)->id\(\) }}/g, 'test')
        .replace(/{{ asset\('([^']+)'\) }}/g, '/$1')
        .replace(/{{ date\('Y-m-d'\) }}/g, new Date().toISOString().slice(0, 10))
        .replace('@csrf', '<input name="_token" value="test" type="hidden">');
    assert.ok(!/@foreach|{{|@if/.test(form), 'test fixture renders the real form without unresolved Blade');
    return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width">' +
        view.match(/<style>[\s\S]*?<\/style>/)[0] +
        '<link rel="stylesheet" href="/vendor/cropperjs/cropper.min.css"></head><body>' + form +
        '<script>window.$=s=>({select2(){return this},on(e,f){document.querySelector(s).addEventListener(e,f);return this}});</script>' +
        '<script src="/vendor/tesseract/tesseract.min.js"></script><script src="/vendor/cropperjs/cropper.min.js"></script>' +
        '<script src="/js/dpi-parser.js"></script><script src="/js/dpi-ocr.js"></script><script src="/js/pedestrian-visit.js"></script></body></html>';
}

function syntheticDpi(options = {}) {
    const card = document.createElement('canvas');
    card.width = 1600; card.height = 1009;
    const ctx = card.getContext('2d');
    ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, 1600, 1009);
    ctx.translate(800, 504); ctx.rotate((options.angle || 0) * Math.PI / 180);
    ctx.translate(-800 + (options.dx || 0), -504 + (options.dy || 0));
    const gradient = ctx.createLinearGradient(0, 0, 1600, 1009);
    gradient.addColorStop(0, '#afcfdf'); gradient.addColorStop(0.5, '#e5efea'); gradient.addColorStop(1, '#b9d4e0');
    ctx.fillStyle = gradient; ctx.fillRect(0, 0, 1600, 1009);
    ctx.strokeStyle = '#a7bdcc'; ctx.lineWidth = 1;
    for (let y = 10; y < 1000; y += 14) {
        ctx.beginPath(); ctx.moveTo(0, y); ctx.bezierCurveTo(400, y - 30, 900, y + 30, 1600, y); ctx.stroke();
    }
    ctx.fillStyle = '#777'; ctx.fillRect(1200, 360, 300, 490);
    ctx.fillStyle = '#a9a9a9'; ctx.beginPath(); ctx.ellipse(1350, 510, 100, 130, 0, 0, Math.PI * 2); ctx.fill();
    ctx.fillStyle = '#bca873'; ctx.fillRect(180, 400, 220, 200);
    ctx.fillStyle = '#17252f';
    function text(value, x, y, size = 42) { ctx.font = '600 ' + size + 'px Arial'; ctx.fillText(value, x, y); }
    text('DOCUMENTO SINTETICO DE PRUEBA', 80, 100, 40);
    text('CODIGO UNICO DE IDENTIFICACION', 60, 230, 20);
    text('CUI', 170, 260, 23);
    text('1234 56789 0101', 65, options.mergeCuiName ? 300 : 325, 42);
    const x = options.old ? 500 : 565;
    const nameRows = options.names || ['MARIA', 'LUCIA'];
    const shift = nameRows.length > 2 ? 40 : 0;
    const nameLabel = options.old ? 'NOMBRE:' : 'NOMBRE / GIVEN NAMES';
    ctx.font = '600 23px Arial';
    const nameLabelWidth = ctx.measureText(nameLabel).width;
    text(nameLabel, x, 245, 23);
    if (options.labelNoise) text('LAA', x + nameLabelWidth + 5, 245, 14);
    nameRows.forEach((name, index) => text(name, x, 300 + index * 50));
    if (options.mergeCuiName) text(nameRows[0].split(/\s+/).slice(-1)[0], x, 300);
    if (options.watermark) {
        ctx.fillStyle = '#71818a';
        text('FONDO', x + 150, 337, 15);
        ctx.fillStyle = '#17252f';
    }
    text(options.old ? 'APELLIDO:' : 'APELLIDOS / SURNAME', x, 390 + shift, 23);
    text('PEREZ', x, 440 + shift); text('LOPEZ', x, 490 + shift);
    text('NACIONALIDAD', x, 565 + shift, 23); text('GTM', x, 610 + shift);
    text('SEXO', x, 660 + shift, 23); text('FEMENINO', x, 700 + shift, 28);
    text('FECHA DE NACIMIENTO', x, 755 + shift, 23); text('17JUL1997', x, 815 + shift);
    text('FIRMA', x, 920 + shift, 23); text('15JUN2023', 1260, 970, 23);
    const output = document.createElement('canvas');
    output.width = options.width || 1600; output.height = Math.round(output.width / 1.586);
    const target = output.getContext('2d');
    target.filter = options.blur ? 'blur(' + options.blur + 'px)' : 'none';
    target.drawImage(card, 0, 0, output.width, output.height);
    return output;
}

async function fakeCamera() {
    const canvas = document.createElement('canvas');
    canvas.width = 1280; canvas.height = 720;
    canvas.getContext('2d').fillRect(0, 0, 1280, 720);
    window.fakeStopped = 0;
    Object.defineProperty(navigator, 'mediaDevices', { configurable: true, value: {
        enumerateDevices: async () => [{ kind: 'videoinput', deviceId: 'droid-test', label: 'DroidCam de prueba' }],
        getUserMedia: async () => {
            const stream = canvas.captureStream(10);
            const timer = setInterval(() => canvas.getContext('2d').fillRect(0, 0, 1280, 720), 80);
            const track = stream.getVideoTracks()[0];
            const stop = track.stop.bind(track);
            track.stop = () => { window.fakeStopped++; clearInterval(timer); stop(); };
            return stream;
        }
    } });
}

async function done(page) {
    await page.waitForFunction(() => document.querySelector('#dpi-progress').hidden && !document.querySelector('#dpi-save').disabled, null, { timeout: 60000 });
}

(async () => {
    const server = http.createServer((request, response) => {
        const pathname = new URL(request.url, 'http://localhost').pathname;
        if (pathname === '/') { response.setHeader('Content-Type', 'text/html; charset=utf-8'); response.end(harness()); return; }
        const filename = path.resolve(root, 'public', '.' + pathname);
        if (!filename.startsWith(path.join(root, 'public') + path.sep)) { response.writeHead(403).end(); return; }
        fs.readFile(filename, (error, bytes) => {
            if (error) { response.writeHead(404).end(); return; }
            response.setHeader('Content-Type', filename.endsWith('.js') ? 'text/javascript' : filename.endsWith('.css') ? 'text/css' : 'application/octet-stream');
            response.end(bytes);
        });
    });
    let browser;
    try {
        await new Promise((resolve, reject) => { server.once('error', reject); server.listen(0, '127.0.0.1', resolve); });
        const base = 'http://127.0.0.1:' + server.address().port;
        browser = await chromium.launch({ headless: true, ...(process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {}) });
        const page = await browser.newPage({ viewport: { width: 1100, height: 900 } });
        const errors = [], external = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('**/*', route => {
            const url = route.request().url();
            if (url.startsWith(base) || url.startsWith('data:') || url.startsWith('blob:')) return route.continue();
            external.push(url); return route.abort();
        });
        await page.goto(base);
        await page.addScriptTag({ content: 'window.syntheticDpi=' + syntheticDpi.toString() });
        const corpus = [
            { name: 'bilingüe con fondo de seguridad', width: 1600 },
            { name: 'formato antiguo y desplazamiento', old: true, width: 1200, dx: -20, dy: 12, angle: 0.6 },
            { name: 'bilingüe con menor resolución y desenfoque', width: 960, dx: 25, angle: -0.8, blur: 0.4 },
            { name: 'formato antiguo de 800px', old: true, width: 800, blur: 0.3 },
            { name: 'un nombre en formato bilingüe', names: ['ANDRES'] },
            { name: 'un nombre junto a ruido de rótulo en formato antiguo', old: true, names: ['ANDRES'], labelNoise: true },
            { name: 'tres nombres en una fila', names: ['JUAN CARLOS ALEJANDRO'], watermark: true },
            { name: 'tres nombres en una fila en formato antiguo', old: true, names: ['JUAN CARLOS ALEJANDRO'] },
            { name: 'tres nombres en dos filas y CUI en la misma línea', names: ['JUAN', 'CARLOS ALEJANDRO'], mergeCuiName: true },
            { name: 'tres nombres en tres filas en formato antiguo', old: true, names: ['JUAN', 'CARLOS', 'ALEJANDRO'] },
            { name: 'tres nombres en tres filas en formato bilingüe', names: ['JUAN', 'CARLOS', 'ALEJANDRO'] }
        ];
        const standard = { Primer_Nombre: 'MARIA', Segundo_Nombre: 'LUCIA', Primer_Apellido: 'PEREZ', Segundo_Apellido: 'LOPEZ', cui: '1234567890101', Fecha_nac: '1997-07-17' };
        const expected = Object.assign({}, standard);
        await page.evaluate(async () => {
            window.realWorker = await Tesseract.createWorker('spa', 1, { workerPath: '/vendor/tesseract/worker.min.js', corePath: '/vendor/tesseract/core', langPath: '/vendor/tesseract/lang' },
                { load_system_dawg: '0', load_freq_dawg: '0' });
        });
        for (const sample of corpus) {
            const expected = Object.assign({}, standard);
            if (sample.names) {
                const names = sample.names.join(' ').trim().split(/\s+/);
                expected.Primer_Nombre = names[0];
                expected.Segundo_Nombre = names.slice(1).join(' ');
            }
            const result = await page.evaluate(async sample => {
                const start = performance.now();
                const samples = [];
                const result = await DpiOcr.read(syntheticDpi(sample), realWorker, { onSample: data => samples.push(data) });
                return { ...result, samples, elapsed: Math.round(performance.now() - start) };
            }, sample);
            console.log(sample.name + ': ' + JSON.stringify({ values: result.values, complete: result.complete, passes: result.passes, elapsed: result.elapsed }));
            assert.deepEqual(result.values, expected, sample.name + ': ' + JSON.stringify(result.samples));
            assert.equal(result.complete, true, sample.name + ': ' + JSON.stringify(result.samples));
            assert.ok(result.passes <= 9);
            assert.equal(result.timedOut, false);
            assert.equal(result.error, null);
        }
        await page.evaluate(async () => { await realWorker.terminate(); });

        const boundaries = await page.evaluate(async () => {
            const image = syntheticDpi({ width: 600 });
            const scale = DpiOcr.scale(image, 1800, true);
            let stopped = 0, calls = 0;
            const stalled = { setParameters: async () => {}, recognize: () => new Promise(() => {}) };
            const timed = await DpiOcr.read(image, stalled, { timeoutMs: 30, onStop: () => stopped++ });
            const empty = { setParameters: async () => {}, recognize: async () => { calls++; return { data: { text: '', blocks: [], confidence: 0 } }; } };
            const limited = await DpiOcr.read(image, empty, { maxPasses: 3 });
            const limitedCalls = calls;
            const exhausted = await DpiOcr.read(image, empty);
            let late, partialCalls = 0, partialStops = 0;
            const partialWorker = { setParameters: async () => {}, recognize: async () => {
                if (!partialCalls++) return { data: { text: '1234 56789 0101', blocks: [], confidence: 90 } };
                return new Promise(resolve => { late = resolve; });
            } };
            const partial = await DpiOcr.read(image, partialWorker, { timeoutMs: 100, onStop: () => partialStops++ });
            const beforeLate = JSON.stringify(partial);
            if (late) late({ data: { text: '9999 99999 9999', blocks: [], confidence: 99 } });
            await new Promise(resolve => setTimeout(resolve, 10));
            return { width: scale.width, warning: DpiOcr.quality(image).warning, timed, stopped, calls: limitedCalls,
                limited, exhausted, partial, partialStops, lateIgnored: beforeLate === JSON.stringify(partial) };
        });
        assert.equal(boundaries.width, 1800);
        assert.match(boundaries.warning, /resolución/);
        assert.equal(boundaries.timed.timedOut, true);
        assert.equal(boundaries.stopped, 1);
        assert.equal(boundaries.calls, 3);
        assert.equal(boundaries.limited.complete, false);
        assert.equal(boundaries.exhausted.passes, 9);
        assert.equal(boundaries.partial.values.cui, expected.cui);
        assert.equal(boundaries.partial.timedOut, true);
        assert.equal(boundaries.partialStops, 1);
        assert.equal(boundaries.lateIgnored, true);
        console.log('PASS: real OCR corpus, real upscaling, quality warning, deadline and pass budget');

        // UI state regression uses deterministic OCR; the corpus above uses the actual local engine.
        await page.addInitScript(fakeCamera);
        await page.goto(base);
        await page.evaluate(() => {
            Tesseract.createWorker = async () => ({ terminate: async () => {} });
            window.analysis = DpiParser.analyze([
                { names: 'MARIA\\nLUCIA'.replace(/\\n/g, '\n'), surnames: 'PEREZ\\nLOPEZ'.replace(/\\n/g, '\n'), cui: '1234567890101', birth: '17JUL1997', confidence: 95, source: 'a' },
                { names: 'MARIA\\nLUCIA'.replace(/\\n/g, '\n'), surnames: 'PEREZ\\nLOPEZ'.replace(/\\n/g, '\n'), cui: '1234567890101', birth: '17JUL1997', confidence: 95, source: 'b' }
            ]);
            window.oneNameAnalysis = DpiParser.analyze([
                { names: 'MARIA', surnames: 'PEREZ\\nLOPEZ'.replace(/\\n/g, '\n'), cui: '1234567890101', birth: '17JUL1997', confidence: 95, source: 'a' },
                { names: 'MARIA', surnames: 'PEREZ\\nLOPEZ'.replace(/\\n/g, '\n'), cui: '1234567890101', birth: '17JUL1997', confidence: 95, source: 'b' }
            ]);
            window.threeNameAnalysis = DpiParser.analyze([
                { names: 'JUAN\\nCARLOS\\nALEJANDRO'.replace(/\\n/g, '\n'), surnames: 'PEREZ\\nLOPEZ'.replace(/\\n/g, '\n'), cui: '1234567890101', birth: '17JUL1997', confidence: 95, source: 'a' },
                { names: 'JUAN\\nCARLOS\\nALEJANDRO'.replace(/\\n/g, '\n'), surnames: 'PEREZ\\nLOPEZ'.replace(/\\n/g, '\n'), cui: '1234567890101', birth: '17JUL1997', confidence: 95, source: 'b' }
            ]);
            window.sharpness = [10, 30, 99, 40, 20];
            window.frameSamples = 0;
            DpiOcr.quality = canvas => { if (canvas.testFrame === undefined) canvas.testFrame = frameSamples; return { sharpness: sharpness[frameSamples++] || 0, warning: '' }; };
            DpiOcr.read = async canvas => {
                window.readWidth = canvas.width;
                window.selectedFrame = canvas.testFrame;
                await new Promise(resolve => setTimeout(resolve, 80));
                if (window.ocrFailure) throw new Error('Simulated OCR failure');
                return window.analysis;
            };
        });
        await page.click('#dpi-start');
        await page.waitForFunction(() => !document.querySelector('#dpi-capture').disabled, null, { timeout: 5000 }).catch(async error => {
            console.log('Camera diagnostics:', await page.locator('#dpi-status').innerText(), errors);
            throw error;
        });
        assert.match(await page.locator('#dpi-camera-quality').innerText(), /1280 × 720/);
        await page.click('#dpi-capture');
        await done(page);
        assert.equal(await page.inputValue('#cui'), expected.cui);
        assert.equal(await page.evaluate(() => frameSamples), 6, 'five frames plus accepted-photo quality measurement');
        assert.equal(await page.evaluate(() => selectedFrame), 2, 'the third frame has the highest sharpness');
        assert.equal(await page.evaluate(() => fakeStopped), 1);
        await page.selectOption('#pedestrian-resident', '1');
        await page.fill('#Primer_Nombre', 'CORREGIDO');
        await page.evaluate(() => { window.analysis = window.oneNameAnalysis; });
        await page.click('#dpi-retry');
        await done(page);
        assert.equal(await page.inputValue('#Primer_Nombre'), 'CORREGIDO');
        assert.equal(await page.locator('#Primer_Nombre').getAttribute('data-ocr-state'), 'manual');
        assert.equal(await page.inputValue('#Segundo_Nombre'), '');
        assert.equal(await page.locator('#Segundo_Nombre').getAttribute('data-ocr-state'), 'empty');
        assert.equal(await page.inputValue('#pedestrian-resident'), '1');
        await page.evaluate(() => { window.analysis = window.threeNameAnalysis; });
        await page.click('#dpi-retry');
        await done(page);
        assert.equal(await page.inputValue('#Primer_Nombre'), 'CORREGIDO');
        assert.equal(await page.inputValue('#Segundo_Nombre'), 'CARLOS ALEJANDRO');
        await page.evaluate(() => { window.ocrFailure = true; });
        await page.click('#dpi-retry');
        await done(page);
        assert.equal(await page.inputValue('#Primer_Nombre'), 'CORREGIDO');
        assert.equal(await page.inputValue('#Segundo_Nombre'), 'CARLOS ALEJANDRO');
        assert.equal(await page.inputValue('#cui'), expected.cui);
        assert.match(await page.locator('#dpi-status').innerText(), /manualmente/);
        assert.equal(await page.locator('#dpi-preview').isVisible(), true);
        await page.evaluate(() => { window.ocrFailure = false; });
        await page.click('#dpi-edit');
        await page.waitForFunction(() => !document.querySelector('#dpi-use-crop').disabled);
        await page.click('#dpi-straighten-left');
        await page.click('#dpi-straighten-right');
        await page.click('#dpi-cancel-crop');
        assert.equal(await page.inputValue('#Primer_Nombre'), 'CORREGIDO');
        await page.click('#dpi-retake');
        await page.click('#dpi-start');
        await page.waitForFunction(() => !document.querySelector('#dpi-capture').disabled);
        await page.evaluate(() => { window.analysis = DpiParser.analyze([]); });
        await page.click('#dpi-capture');
        await done(page);
        for (const field of fieldNames) assert.equal(await page.inputValue('#' + field), '', 'new photo clears ' + field);
        assert.equal(await page.inputValue('#pedestrian-resident'), '1');
        assert.match(await page.locator('#cui-ocr').innerText(), /Complete/);
        for (const size of [{ width: 390, height: 844 }, { width: 820, height: 1180 }]) {
            await page.setViewportSize(size);
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth));
        }
        console.log('PASS: burst capture, retry, manual edits, failure recovery, crop cancellation and new photo reset');

        // Real native-image -> Cropper -> local OCR -> client save contract, with no database writes.
        await page.goto(base);
        await page.addScriptTag({ content: 'window.syntheticDpi=' + syntheticDpi.toString() });
        const photo = await page.evaluate(() => syntheticDpi({ width: 1200 }).toDataURL('image/png').split(',')[1]);
        await page.locator('#dpi-native-file').setInputFiles({ name: 'dpi-sintetico.png', mimeType: 'image/png', buffer: Buffer.from(photo, 'base64') });
        await page.waitForFunction(() => !document.querySelector('#dpi-use-crop').disabled);
        await page.click('#dpi-use-crop');
        await done(page);
        for (const field of fieldNames) assert.equal(await page.inputValue('#' + field), expected[field], 'actual UI OCR: ' + field);
        await page.fill('#Primer_Nombre', 'CORREGIDO');
        await page.click('#dpi-retry');
        await done(page);
        assert.equal(await page.inputValue('#Primer_Nombre'), 'CORREGIDO');
        await page.click('#dpi-edit');
        await page.waitForFunction(() => !document.querySelector('#dpi-use-crop').disabled);
        await page.click('#dpi-use-crop');
        await done(page);
        assert.equal(await page.inputValue('#Primer_Nombre'), 'CORREGIDO');
        assert.equal(await page.inputValue('#cui'), expected.cui);
        await page.selectOption('#pedestrian-resident', '1');
        let saves = 0;
        await page.route('**/save', route => {
            const request = route.request();
            const body = request.postDataBuffer().toString('latin1');
            assert.equal(request.method(), 'POST');
            assert.match(request.headers()['content-type'], /multipart\/form-data/);
            assert.match(body, /name="photo_dpi"; filename="dpi.jpg"/);
            assert.match(body, /Content-Type: image\/jpeg/);
            assert.match(body, /name="Primer_Nombre"\r\n\r\nCORREGIDO/);
            assert.match(body, /name="cui"\r\n\r\n1234567890101/);
            saves++;
            return route.fulfill({ status: saves === 1 ? 503 : 201, contentType: 'application/json',
                body: JSON.stringify(saves === 1 ? { message: 'Fallo de guardado simulado' } : { redirect_url: base + '/saved' }) });
        });
        await page.click('#dpi-save');
        await page.waitForFunction(() => !document.querySelector('#dpi-errors').hidden);
        assert.equal(await page.inputValue('#Primer_Nombre'), 'CORREGIDO');
        assert.equal(await page.locator('#dpi-preview').isVisible(), true);
        await Promise.all([page.waitForURL('**/saved'), page.click('#dpi-save')]);
        assert.equal(saves, 2);
        assert.deepEqual(errors, []);
        assert.deepEqual(external, []);
        console.log('PASS: real image upload, crop, local OCR, retry/reframe preserving edits, save failure/retry and local-only requests');
    } finally {
        if (browser) await browser.close();
        await new Promise(resolve => server.close(resolve));
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
