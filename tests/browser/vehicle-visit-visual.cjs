// Run before and after the migration against the disposable visits instance.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.env.PLAYWRIGHT_MODULE || 'playwright-core');
const base = process.env.VISITS_BASE_URL || 'http://localhost:18880';
if (!['localhost', '127.0.0.1'].includes(new URL(base).hostname) || new URL(base).port !== '18880') throw new Error('Only the isolated visits instance is allowed.');
const output = process.env.VISITS_SCREENSHOT_DIR;
if (!output) throw new Error('Set VISITS_SCREENSHOT_DIR to an artifact directory.');
(async () => {
    fs.mkdirSync(output, {recursive:true});
    const browser = await chromium.launch({channel:'chrome', headless:true});
    try {
        const context = await browser.newContext({viewport:{width:1366,height:1000}});
        await context.route('**/*', route => route.request().url().startsWith(base) ? route.continue() : route.abort());
        await context.addInitScript(() => Object.defineProperty(navigator, 'mediaDevices', {configurable:true, value:{
            getUserMedia:async()=>{throw new DOMException('Synthetic visual test', 'NotAllowedError');}, enumerateDevices:async()=>[]
        }}));
        const check = await context.request.post(base+'/_visits_test_control', {headers:{'X-Visits-Test':'isolated-visits-test'},data:{photos:[0,0,0]}});
        assert.equal(check.status(),200);assert.equal((await check.json()).database,'garita_visits_test');
        const page = await context.newPage();
        await page.clock.install({time:new Date('2026-10-18T18:00:00Z')});
        await page.goto(base+'/login');
        await page.fill('[name=username]','admin');await page.fill('[name=password]','visits-test-only');
        await Promise.all([page.waitForURL(url=>!url.pathname.includes('/login')),page.click('button[type=submit]')]);
        await page.goto(base+'/visit/create');
        await page.waitForFunction(()=>!!window.jQuery?.('#selectResidente').data('select2'));
        async function shot(name) {
            await page.locator('#responset').evaluate(el=>{el.hidden=true;});
            await page.evaluate(()=>document.activeElement?.blur());
            await page.screenshot({path:path.join(output,(process.env.VISITS_VISUAL_PHASE || 'after')+'-'+name+'.png'),fullPage:true,animations:'disabled'});
        }
        await shot('desktop-empty');
        await page.locator('#select2-selectResidente-container').click();
        await page.locator('.select2-search__field').fill('C202');
        await shot('desktop-search');
        await page.getByRole('option',{name:'C202',exact:true}).click();
        await page.waitForFunction(()=>document.querySelector('#direccion').value==='Casa B-34');
        await page.fill('#placa','P123ABC');await page.fill('#cono','17');
        await page.fill('#data',['12345678','C','JUAN','','PEREZ','','01/01/1990','01/01/2030','1234567890101','12345678',''].join('|'));
        await page.press('#data','Enter');
        await page.waitForFunction(()=>document.querySelector('[id="1nombre"]').textContent==='JUAN');
        await shot('desktop-scanned');
        await page.setViewportSize({width:390,height:844});
        await shot('mobile-scanned');
        await context.close();
        console.log('Saved four visual references: '+output);
    } finally {await browser.close();}
})().catch(error=>{console.error(error.stack);process.exitCode=1;});
