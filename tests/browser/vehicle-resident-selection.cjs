// Exercise the restored Select2 search and its Livewire binding in both browsers.
const assert = require('node:assert/strict');
const {chromium} = require(process.env.PLAYWRIGHT_MODULE || 'playwright-core');
const base = process.env.VISITS_BASE_URL || 'http://localhost:18880';
if (!['localhost','127.0.0.1','web','garita-vehicle-test-web-1'].includes(new URL(base).hostname)) throw new Error('Only the isolated test server is allowed.');

(async () => {
    for (const channel of (process.env.VISITS_BROWSER ? [process.env.VISITS_BROWSER] : ['msedge','chrome'])) {
        const browser = await chromium.launch({channel:channel==='chromium'?undefined:channel==='edge'?'msedge':channel,executablePath:process.env.CHROMIUM_PATH,headless:true});
        try {
            const context = await browser.newContext({viewport:{width:1366,height:1000}});
            await context.addInitScript(() => Object.defineProperty(navigator,'mediaDevices',{configurable:true,value:{
                getUserMedia:async()=>{throw new DOMException('No camera for resident selector test','NotAllowedError');},
                enumerateDevices:async()=>[]
            }}));
            await context.route('**/*',route=>route.request().url().startsWith(base)?route.continue():route.abort());
            const page=await context.newPage(),errors=[],requests=[];
            page.setDefaultTimeout(10000);
            page.on('pageerror',e=>{if(page.url().includes('/visit/'))errors.push(e.message);});
            const endpoint='**/livewire/message/vehicle-visit';
            const lookup=request=>new URL(request.url()).pathname==='/livewire/message/vehicle-visit'
                && request.method()==='POST'
                && request.postDataJSON().updates.find(update=>update.type==='syncInput'&&update.payload.name==='residente');
            page.on('request',request=>{if(lookup(request))requests.push(request.postDataJSON());});
            const check=await context.request.post(base+'/_visits_test_control',{headers:{'X-Visits-Test':'isolated-visits-test'},data:{photos:[0,0,0]}});
            assert.equal(check.status(),200);assert.equal((await check.json()).database,'garita_visits_test');
            await page.goto(base+'/login');await page.fill('[name=username]','admin');await page.fill('[name=password]','visits-test-only');
            await Promise.all([page.waitForURL(url=>!url.pathname.includes('/login')),page.click('button[type=submit]')]);
            await page.goto(base+'/visit/create');
            const originalUuid=await page.locator('#vehicle-visit-form').getAttribute('data-submission-id');
            const select=page.locator('#selectResidente');
            async function search(value) {
                if (!await page.locator('.select2-container--open .select2-search__field').count()) {
                    await page.locator('#selectResidente + .select2 .select2-selection').click();
                }
                const input=page.locator('.select2-container--open .select2-search__field');
                await input.fill('');
                await input.pressSequentially(value);
            }
            async function choose(value) {
                await page.evaluate(value => window.jQuery('#selectResidente').val(value).trigger('change'), value);
            }
            async function matching(value,id,label){
                await search(value);
                await page.locator('.select2-results__option').filter({hasText:label}).first().waitFor();
                await page.keyboard.press('Escape');
            }
            async function details(code,address){
                await page.waitForFunction(([code,address])=>document.querySelector('#ftelefono').value===code
                    &&document.querySelector('#direccion').value===address,[code,address],{timeout:10000});
            }
            await search('No existe');
            await page.getByText('No se encontraron residentes activos.').waitFor();
            await search('C101');
            await page.locator('.select2-results__option').filter({hasText:'C101'}).click();
            await details('A-12','Casa A-12');
            assert.equal(requests.length,1,'a Select2 selection produces one Livewire resident update');
            console.log('PASS '+channel+': searches access number and Select2 updates house/address');
            await matching('A-12','1','A-12');
            await matching('Segundo Residente','2','Segundo');
            await choose('2');await details('B-34','Casa B-34');
            assert.equal(requests.length,2);
            await search('sin coincidencias');
            await page.getByText('No se encontraron residentes activos.').waitFor().catch(async error=>{
                console.error(await page.evaluate(()=>({
                    inputs:Array.from(document.querySelectorAll('.select2-search__field')).map(input=>({value:input.value,connected:input.isConnected})),
                    results:document.querySelector('.select2-results')?.textContent,
                    open:window.jQuery('#selectResidente').data('select2')?.isOpen(),
                    resident:document.querySelector('#selectResidente').value,
                })));
                throw error;
            });
            await page.keyboard.press('Escape');
            assert.equal(await select.inputValue(),'2','changing the query keeps the selected active resident visible');
            assert.ok(await select.locator('option:checked').textContent().then(text=>text.includes('C202')));
            await page.waitForFunction(()=>JSON.parse(sessionStorage.getItem('garita.vehicle-visit.v1.'+document.querySelector('#vehicle-visit-form').dataset.userId)||'null')?.inputs?.residente==='2');
            const draft=await page.evaluate(()=>JSON.parse(sessionStorage.getItem('garita.vehicle-visit.v1.'+document.querySelector('#vehicle-visit-form').dataset.userId)));
            assert.equal(draft.inputs.residentSearch,undefined,'transient search text is excluded from registration backup');
            await page.reload();await details('B-34','Casa B-34');
            assert.equal(await page.locator('#vehicle-visit-form').getAttribute('data-submission-id'),originalUuid);
            assert.match(await select.locator('option:checked').textContent(),/C202/);
            console.log('PASS '+channel+': name/code search, selection and backup reload retain the selected house');

            // A slower previous resident response must not erase newer local edits.
            let release,startedResolve;const started=new Promise(resolve=>startedResolve=resolve);const gate=new Promise(resolve=>release=resolve);
            let intercepted=false;
            const delayed=async route=>{
                const action=lookup(route.request());
                if(!action||String(action.payload.value)!=='1'||intercepted){await route.continue();return;}
                intercepted=true;
                const actual=await route.fetch();startedResolve();await gate;
                await route.fulfill({response:actual}).catch(()=>{});
            };
            await page.route(endpoint,delayed);
            await choose('1');await started;
            await page.fill('#placa','EDIT123');await page.fill('#cono','98');await page.click('.switch label');
            await choose('2');
            release();await page.unroute(endpoint,delayed);
            await details('B-34','Casa B-34');
            assert.equal(await page.inputValue('#placa'),'EDIT123');assert.equal(await page.inputValue('#cono'),'98');
            assert.equal(await page.isChecked('#es_repartidor'),true);
            await page.evaluate(()=>document.querySelector('[data-vehicle-visit-component]').__livewire.call('$refresh'));
            assert.equal(await page.locator('#vehicle-visit-form').getAttribute('data-submission-id'),originalUuid);
            await details('B-34','Casa B-34');
            assert.equal(await page.inputValue('#placa'),'EDIT123');assert.equal(await page.isChecked('#es_repartidor'),true);
            console.log('PASS '+channel+': a slow selection response preserves newer form edits');

            const failing=async route=>{
                const action=lookup(route.request());
                if(action&&String(action.payload.value)==='1')await route.fulfill({status:503,contentType:'application/json',body:'{}'});
                else await route.continue();
            };
            await page.route(endpoint,failing);
            await matching('C101','1','C101');
            await choose('1');await page.locator('#vehicle-transport-alert').waitFor({state:'visible'});
            assert.equal(await page.locator('#vehicle-visit-form').evaluate(element=>element.inert),true);
            await page.unroute(endpoint,failing);await page.click('#vehicle-reconnect');
            await page.locator('#vehicle-transport-alert').waitFor({state:'hidden'});
            await details('A-12','Casa A-12');
            console.log('PASS '+channel+': transport failure recovers the selected resident through the saved draft');

            const beforeClear=requests.length;
            await choose('');await details('','');
            assert.equal(requests.length,beforeClear+1);
            const beforeSelect=requests.length;
            await matching('C101','1','C101');await choose('1');await details('A-12','Casa A-12');
            assert.equal(requests.length,beforeSelect+1);
            console.log('PASS '+channel+': Select2 selection clears and changes without duplicate lookups');

            await matching('C202','2','C202');await choose('2');await details('B-34','Casa B-34');
            await page.fill('#placa',channel==='chrome'?'RESCHR':'RESEDGE');await page.fill('#cono','1');
            const code=['12345678','C','JUAN','','PEREZ','','01/01/1990','01/01/2030','1234567890101','12345678',''].join('|');
            await page.fill('#data',code);await page.press('#data','Enter');
            let posted;
            page.on('request',request=>{
                if(request.method()==='POST'&&new URL(request.url()).pathname==='/livewire/message/vehicle-visit') {
                    posted=request.postDataJSON().updates.find(update=>update.payload.method==='save')?.payload.params;
                }
            });
            await Promise.all([page.waitForURL('**/visit/detailvisit/*'),page.click('#guardar')]);
            assert.deepEqual(posted,[],'save uses component state without a browser-built visitor payload');
            assert.match(await page.locator('body').innerText(),/Segundo\s+Residente/);
            assert.deepEqual(errors,[]);
            console.log('PASS '+channel+': saved visit uses the resident selected with Select2 through Livewire');
            await context.close();
        } finally {await browser.close();}
    }
})().catch(error=>{console.error(error.stack);process.exitCode=1;});
