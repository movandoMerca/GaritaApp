// Scanner focus and selector layout on the disposable visits instance only.
const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright-core');
const base = 'http://localhost:18880';
const code = name => ['123456789', 'M', name, '', 'PEREZ', '', '17-08-1987', '17-08-2029', '1234567890101', '12345678', ''].join(']');

(async () => {
    for (const channel of ['msedge', 'chrome']) {
        const browser = await chromium.launch({ channel, headless: true });
        try {
            const context = await browser.newContext({ viewport: { width: 1366, height: 1000 } });
            await context.route('**/*', route => route.request().url().startsWith(base) ? route.continue() : route.abort());
            const control = () => context.request.post(base + '/_visits_test_control', {
                headers: { 'X-Visits-Test': 'isolated-visits-test' }, data: { photos: [0, 0, 0] },
            });
            const check = await control();
            assert.equal(check.status(), 200);
            const initial = await check.json();
            assert.equal(initial.database, 'garita_visits_test');
            assert.equal(initial.storage, 'local');
            const page = await context.newPage();
            const errors = [];
            await page.goto(base + '/login');
            await page.fill('[name=username]', 'admin');
            await page.fill('[name=password]', 'visits-test-only');
            await Promise.all([page.waitForURL(url => !url.pathname.includes('/login')), page.click('button[type=submit]')]);
            page.on('pageerror', error => errors.push(error.stack));
            const focused = () => page.waitForFunction(() => document.activeElement?.id === 'data');
            const ready = () => page.waitForFunction(() => {
                const component = document.querySelector('[data-vehicle-visit-component]').__livewire;
                return !component.messageInTransit && !component.updateQueue.length;
            });
            await page.evaluate(() => sessionStorage.setItem('garita.vehicle-visit.v1.1', JSON.stringify({version:2, inputs:{placa:'ANTERIOR', codigoLicencia:'VIEJO'}})));
            await page.goto(base + '/visit/create');
            await focused();
            assert.equal(await page.evaluate(() => sessionStorage.getItem('garita.vehicle-visit.v1.1')), null);
            let previousUuid = await page.locator('#vehicle-visit-form').getAttribute('data-submission-id');
            await page.keyboard.type('LECTURA PARCIAL');
            assert.equal(await page.evaluate(() => sessionStorage.getItem('garita.vehicle-visit.v1.1')), null);
            await page.reload();
            await ready();
            await focused();
            assert.equal(await page.inputValue('#data'), '');
            assert.notEqual(await page.locator('#vehicle-visit-form').getAttribute('data-submission-id'), previousUuid);
            await page.keyboard.type(code('ANA'));
            await page.keyboard.press('Enter');
            await page.waitForFunction(() => document.getElementById('1nombre').textContent === 'ANA');
            await ready();
            await page.reload();
            await ready();
            await focused();
            assert.equal(await page.locator('#data').isVisible(), true);
            assert.equal(await page.locator('[id="1nombre"]').textContent(), '');
            assert.equal(await page.inputValue('#placa'), '');
            await page.keyboard.type(code('PEDRO'));
            await page.keyboard.press('Enter');
            await page.waitForFunction(() => document.getElementById('1nombre').textContent === 'PEDRO');
            await ready();

            await page.click('#abrirIngresoManual');
            await page.waitForFunction(() => document.activeElement?.id === 'nombre-manual');
            await page.locator('#modalimg').getByRole('button', { name: 'Cancelar', exact: true }).click();
            await page.locator('#modalimg').waitFor({ state: 'hidden' });
            await focused();

            await page.locator('#selectResidente + .select2 .select2-selection').click();
            const search = page.locator('.select2-container--open .select2-search__field');
            await search.fill('C202');
            await page.evaluate(() => document.querySelector('[data-vehicle-visit-component]').__livewire.call('$refresh'));
            assert.equal(await search.evaluate(input => document.activeElement === input), true);
            await page.locator('.select2-results__option').filter({ hasText: 'C202' }).click();
            await page.waitForFunction(() => document.querySelector('#ftelefono').value === 'B-34');
            await ready();

            for (const width of [1366, 390]) {
                await page.setViewportSize({ width, height: 1000 });
                await page.reload();
                    await ready();
                await focused();
                assert.equal(await page.locator('#selectResidente').inputValue(), '');
                await page.evaluate(() => window.jQuery('#selectResidente').val('2').trigger('change'));
                await page.waitForFunction(() => document.querySelector('#ftelefono').value === 'B-34');
                await ready();
                const layout = await page.evaluate(() => {
                    const group = document.querySelector('.resident-select-group');
                    const icon = group.querySelector('.input-group-prepend').getBoundingClientRect();
                    const select = group.querySelector('.select2-selection').getBoundingClientRect();
                    const clear = group.querySelector('.select2-selection__clear').getBoundingClientRect();
                    return { aligned: Math.abs(icon.y - select.y) < 1, sameHeight: Math.abs(icon.height - select.height) < 1,
                        clearInside: clear.x >= select.x && clear.right <= select.right,
                        fits: select.right <= group.getBoundingClientRect().right + 1 };
                });
                assert.deepEqual(layout, { aligned: true, sameHeight: true, clearInside: true, fits: true });
            }
            const after = await (await control()).json();
            assert.equal(after.visits.length, initial.visits.length, 'Scanning and focusing must not register a visit');
            assert.deepEqual(errors, []);
            console.log('PASS ' + channel + ': automatic code focus, scanner input, clean reload without drafts, manual/Select2 focus and desktop/mobile alignment');
            await context.close();
        } finally { await browser.close(); }
    }
})().catch(error => { console.error(error.stack); process.exitCode = 1; });
