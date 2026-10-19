// Click the actual Select2 UI: selectOption alone does not exercise its jQuery events.
const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright-core');
const base = process.env.VISITS_BASE_URL || 'http://localhost:18880';
if (!['localhost','127.0.0.1','web','garita-vehicle-test-web-1'].includes(new URL(base).hostname)) throw new Error('Only the isolated test server is allowed.');
(async () => {
    for (const channel of (process.env.VISITS_BROWSER ? [process.env.VISITS_BROWSER] : ['msedge', 'chrome'])) {
        const browser = await chromium.launch({channel:channel==='chromium'?undefined:channel==='edge'?'msedge':channel,executablePath:process.env.CHROMIUM_PATH,headless:true});
        try {
            const context = await browser.newContext({viewport:{width:1366,height:1000}});
            await context.addInitScript(() => Object.defineProperty(navigator,'mediaDevices',{configurable:true,value:{
                getUserMedia:async()=>{throw new DOMException('No camera for resident selector test','NotAllowedError');},
                enumerateDevices:async()=>[]
            }}));
            await context.route('**/*',route=>route.request().url().startsWith(base)?route.continue():route.abort());
            const page=await context.newPage();const errors=[];const requests=[];
            page.on('pageerror',e=>{if(page.url().includes('/visit/'))errors.push(e.message);});
            const endpoint='**/livewire/message/vehicle-visit';
            const lookup=r=>new URL(r.url()).pathname==='/livewire/message/vehicle-visit'&&r.method()==='POST'&&r.postDataJSON().updates.find(update=>update.type==='syncInput'&&update.payload.name==='residente');
            page.on('request',r=>{if(lookup(r))requests.push(r.postDataJSON());});
            const check=await context.request.post(base+'/_visits_test_control',{headers:{'X-Visits-Test':'isolated-visits-test'},data:{photos:[0,0,0]}});
            assert.equal(check.status(),200);assert.equal((await check.json()).database,'garita_visits_test');
            await page.goto(base+'/login');await page.fill('[name=username]','admin');await page.fill('[name=password]','visits-test-only');
            await Promise.all([page.waitForURL(url=>!url.pathname.includes('/login')),page.click('button[type=submit]')]);
            await page.goto(base+'/visit/create');
            const originalUuid=await page.locator('#vehicle-visit-form').getAttribute('data-submission-id');
            async function select(label){
                await page.locator('#select2-selectResidente-container').click();
                await page.locator('.select2-search__field').fill(label);
                await page.getByRole('option',{name:label,exact:true}).click();
            }
            async function details(code,address){
                await page.waitForFunction(([code,address])=>document.querySelector('#ftelefono').value===code&&document.querySelector('#direccion').value===address,[code,address],{timeout:10000});
            }
            await select('C101');
            assert.equal(await page.inputValue('#selectResidente'),'1');
            await details('A-12','Casa A-12').catch(async error=>{
                console.error(JSON.stringify({requests:requests.length,...await page.evaluate(()=>({selected:document.querySelector('#selectResidente').value,house:document.querySelector('#ftelefono').value,address:document.querySelector('#direccion').value}))}));
                throw error;
            });
            assert.equal(requests.length,1,'one lookup per Select2 selection');
            console.log('PASS '+channel+': real Select2 click fills house/address');
            await select('C202');await details('B-34','Casa B-34');
            assert.equal(requests.length,2);
            await page.waitForFunction(()=>JSON.parse(sessionStorage.getItem('garita.vehicle-visit.v1.'+document.querySelector('#vehicle-visit-form').dataset.userId)||'null')?.inputs?.residente==='2');
            await page.reload();await details('B-34','Casa B-34');
            assert.equal(await page.locator('#vehicle-visit-form').getAttribute('data-submission-id'),originalUuid);
            assert.equal(await page.locator('#select2-selectResidente-container').innerText(),'C202');
            console.log('PASS '+channel+': selection change and draft reload preserve matching details');

            // Delay house 1 until after house 2 has completed: the earlier response
            // must never replace the current house/address.
            let release,startedResolve;const started=new Promise(r=>startedResolve=r);const gate=new Promise(r=>release=r);
            let intercepts=0;
            const delayed=async route=>{
                const action=lookup(route.request());
                if (!action || String(action.payload.value)!=='1' || intercepts++) {await route.continue();return;}
                const actual=await route.fetch();startedResolve();await gate;
                await route.fulfill({response:actual}).catch(()=>{});
            };
            await page.route(endpoint,delayed);
            await select('C101');await started;
            // New local edits while the older component request is pending must
            // survive both the newer reply and an explicit component render.
            await page.fill('#placa','EDIT123');await page.fill('#cono','98');await page.click('.switch label');
            await select('C202');await details('B-34','Casa B-34');
            release();await page.unroute(endpoint,delayed);await page.evaluate(()=>new Promise(r=>setTimeout(r,100)));
            await details('B-34','Casa B-34');
            assert.equal(await page.inputValue('#placa'),'EDIT123');assert.equal(await page.inputValue('#cono'),'98');assert.equal(await page.isChecked('#es_repartidor'),true);
            await page.evaluate(()=>document.querySelector('[data-vehicle-visit-component]').__livewire.call('$refresh'));
            assert.equal(await page.locator('#vehicle-visit-form').getAttribute('data-submission-id'),originalUuid);
            await details('B-34','Casa B-34');
            assert.equal(await page.inputValue('#placa'),'EDIT123');assert.equal(await page.isChecked('#es_repartidor'),true);
            console.log('PASS '+channel+': slow previous response cannot overwrite current selection');

            // A transport failure blocks edits until PHP verifies the state.
            const failing=async route=>{
                const action=lookup(route.request());
                if (action&&String(action.payload.value)==='1') await route.fulfill({status:503,contentType:'application/json',body:'{}'});
                else await route.continue();
            };
            await page.route(endpoint,failing);
            await select('C101');await page.locator('#vehicle-transport-alert').waitFor({state:'visible'});
            assert.equal(await page.locator('#vehicle-visit-form').evaluate(el=>el.inert),true);
            await page.unroute(endpoint,failing);
            await page.click('#vehicle-reconnect');
            await page.locator('#vehicle-transport-alert').waitFor({state:'hidden'});
            await select('C202');await details('B-34','Casa B-34');
            console.log('PASS '+channel+': failed lookup requires server recovery');

            await page.evaluate(()=>{const select=document.querySelector('#selectResidente');select.value='';window.jQuery(select).trigger('change');});
            await details('','');
            // Native change remains supported as well, without two requests.
            const before=requests.length;
            await page.selectOption('#selectResidente','1');await details('A-12','Casa A-12');assert.equal(requests.length,before+1);
            console.log('PASS '+channel+': clear and native select events work without duplicate lookups');

            // Save through the actual form after a real Select2 choice and check
            // the persisted resident, not just the two informational fields.
            await select('C202');await details('B-34','Casa B-34');
            await page.fill('#placa',channel==='chrome'?'RESCHR':'RESEDGE');await page.fill('#cono','1');
            const code=['12345678','C','JUAN','','PEREZ','','01/01/1990','01/01/2030','1234567890101','12345678',''].join('|');
            await page.fill('#data',code);await page.press('#data','Enter');
            let posted;
            page.on('request',r=>{if(r.method()==='POST'&&new URL(r.url()).pathname==='/livewire/message/vehicle-visit')posted=r.postDataJSON().updates.find(update=>update.payload.method==='save')?.payload.params;});
            await Promise.all([page.waitForURL('**/visit/detailvisit/*'),page.click('#guardar')]);
            assert.deepEqual(posted,[],'save uses component state without JS payload');
            assert.match(await page.locator('body').innerText(),/Segundo\s+Residente/);
            assert.deepEqual(errors,[]);
            console.log('PASS '+channel+': saved visit belongs to selected resident; no uncaught errors');
            await context.close();
        } finally {await browser.close();}
    }
})().catch(error=>{console.error(error.stack);process.exitCode=1;});
