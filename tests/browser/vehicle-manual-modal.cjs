// Uses only the disposable visits instance; never production credentials or data.
const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright-core');
const base = 'http://localhost:18880';

(async () => {
    for (const channel of ['msedge', 'chrome']) {
        const browser = await chromium.launch({ channel, headless: true });
        try {
            const context = await browser.newContext({ viewport: { width: 1366, height: 1000 } });
            await context.route('**/*', route => route.request().url().startsWith(base) ? route.continue() : route.abort());
            const response = await context.request.post(base + '/_visits_test_control', {
                headers: { 'X-Visits-Test': 'isolated-visits-test' }, data: { photos: [0, 0, 0] },
            });
            assert.equal(response.status(), 200);
            const initial = await response.json();
            assert.equal(initial.database, 'garita_visits_test');
            assert.equal(initial.storage, 'local');
            const page = await context.newPage(), errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.goto(base + '/login');
            await page.fill('[name=username]', 'admin');
            await page.fill('[name=password]', 'visits-test-only');
            await Promise.all([page.waitForURL(url => !url.pathname.includes('/login')), page.click('button[type=submit]')]);
            await page.goto(base + '/visit/create');
            const modal = page.locator('#modalimg');
            const waitClosed = async () => {
                await modal.waitFor({ state: 'hidden' });
                await page.waitForFunction(() => !document.body.classList.contains('modal-open') && !document.querySelector('.modal-backdrop'));
                assert.equal(await page.locator('#placa').isEditable(), true);
            };
            await page.click('#abrirIngresoManual');
            await modal.waitFor({ state: 'visible' });
            assert.equal(await page.locator('#nombre-manual').isEditable(), true);
            await page.click('#guardarManual');
            await modal.getByText('Ingrese el nombre del visitante para continuar.').waitFor();
            await page.fill('#nombre-manual', 'VISITANTE DE PRUEBA');
            await page.press('#nombre-manual', 'Enter');
            await waitClosed();
            await page.locator('[id="1nombre"]').getByText('VISITANTE DE PRUEBA').waitFor();

            await page.click('#abrirIngresoManual');
            await page.fill('#nombre-manual', 'BORRADOR MODAL');
            await page.reload();
            await page.waitForFunction(() => !document.querySelector('[data-vehicle-visit-component]').__livewire.messageInTransit);
            assert.equal(await page.inputValue('#nombre-manual'), '');
            assert.equal(await page.locator('[id="1nombre"]').textContent(), '');
            await page.click('#abrirIngresoManual');
            await modal.waitFor({ state: 'visible' });
            await modal.getByRole('button', { name: 'Cancelar', exact: true }).click();
            await waitClosed();

            await page.click('#abrirIngresoManual');
            await page.waitForFunction(() => document.querySelector('#modalimg').classList.contains('show') && !window.jQuery('#modalimg').data('bs.modal')._isTransitioning);
            await page.keyboard.press('Escape');
            await waitClosed();
            await page.click('#abrirIngresoManual');
            await page.fill('#nombre-manual', 'VISITANTE MANUAL');
            await page.click('#guardarManual');
            await waitClosed();
            await page.evaluate(() => window.jQuery('#selectResidente').val('1').trigger('change'));
            await page.waitForFunction(() => document.querySelector('#ftelefono').value === 'A-12');
            await page.fill('#placa', channel === 'chrome' ? 'MODALC' : 'MODALE');
            await page.fill('#cono', '1');
            try {
                await Promise.all([page.waitForURL('**/visit/detailvisit/*', { timeout: 15000 }), page.click('#guardar')]);
            } catch (error) {
                console.error(await page.evaluate(() => {
                    const root = document.querySelector('[data-vehicle-visit-component]');
                    return { url: location.href, notice: document.querySelector('#responset')?.textContent,
                        transportBlocked: document.querySelector('#vehicle-visit-form')?.inert,
                        status: root?.__livewire.data.status, resident: root?.__livewire.data.residente,
                        manual: root?.__livewire.data.nombreManual, plate: root?.__livewire.data.placa };
                }));
                throw error;
            }
            const afterResponse = await context.request.post(base + '/_visits_test_control', { headers: { 'X-Visits-Test': 'isolated-visits-test' }, data: {} });
            const after = await afterResponse.json();
            assert.equal(after.visits.length, initial.visits.length + 1);
            assert.equal(after.visits.find(visit => Number(visit.id) === Number(page.url().split('/').pop())).Primer_Nombre, 'VISITANTE MANUAL');
            assert.deepEqual(errors, []);
            console.log('PASS ' + channel + ': previous modal, validation, Enter, clean reload, Cancel, Escape and manual registration');
            await context.close();
        } finally { await browser.close(); }
    }
})().catch(error => { console.error(error.stack); process.exitCode = 1; });
