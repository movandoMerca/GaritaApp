// Click the actual Select2 UI: selectOption alone does not exercise its jQuery events.
const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright-core');
const base = process.env.VISITS_BASE_URL || 'http://localhost:18880';
if (!['localhost','127.0.0.1','web','garita-vehicle-test-web-1'].includes(new URL(base).hostname)) throw new Error('Only the isolated test server is allowed.');
(async () => {
    for (const channel of (process.env.VISITS_BROWSER ? [process.env.VISITS_BROWSER] : (process.env.VISITS_RESIDENT_BASELINE ? ['msedge'] : ['msedge', 'chrome']))) {
        const browser = await chromium.launch({channel:channel==='chromium'?undefined:channel,executablePath:process.env.CHROMIUM_PATH,headless:true});
        try {
            const context = await browser.newContext({viewport:{width:1366,height:1000}});
            await context.addInitScript(() => Object.defineProperty(navigator,'mediaDevices',{configurable:true,value:{
                getUserMedia:async()=>{throw new DOMException('No camera for resident selector test','NotAllowedError');},
                enumerateDevices:async()=>[]
            }}));
            await context.route('**/*',route=>route.request().url().startsWith(base)?route.continue():route.abort());
            if (process.env.VISITS_RESIDENT_BASELINE_SOURCE) {
                const source=require('node:fs').readFileSync(process.env.VISITS_RESIDENT_BASELINE_SOURCE,'utf8');
                await context.route('**/js/vehicle-visit.js*',route=>route.fulfill({status:200,contentType:'text/javascript',body:source}));
            }
            const page=await context.newPage();const errors=[];const requests=[];
            page.on('pageerror',e=>{if(page.url().includes('/visit/'))errors.push(e.message);});
            page.on('request',r=>{if(new URL(r.url()).pathname==='/resident/ajax')requests.push(r.url());});
            const check=await context.request.post(base+'/_visits_test_control',{headers:{'X-Visits-Test':'isolated-visits-test'},data:{photos:[0,0,0]}});
            assert.equal(check.status(),200);assert.equal((await check.json()).database,'garita_visits_test');
            await page.goto(base+'/login');await page.fill('[name=username]','admin');await page.fill('[name=password]','visits-test-only');
            await Promise.all([page.waitForURL(url=>!url.pathname.includes('/login')),page.click('button[type=submit]')]);
            await page.goto(base+'/visit/create');
            async function select(label){
                await page.locator('#select2-selectResidente-container').click();
                await page.locator('.select2-search__field').fill(label);
                await page.getByRole('option',{name:label,exact:true}).click();
            }
            async function details(code,address){
                await page.waitForFunction(([code,address])=>document.querySelector('#ftelefono').value===code&&document.querySelector('#direccion').value===address,[code,address],{timeout:process.env.VISITS_RESIDENT_BASELINE?3000:10000});
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
            await page.waitForFunction(()=>JSON.parse(sessionStorage.getItem('garita.vehicle-visit.v1.'+document.querySelector('#vehicle-visit-form').dataset.userId)||'null')?.resident==='2');
            await page.reload();await details('B-34','Casa B-34');
            assert.equal(await page.locator('#select2-selectResidente-container').innerText(),'C202');
            console.log('PASS '+channel+': selection change and draft reload preserve matching details');

            // Delay house 1 until after house 2 has completed: the earlier response
            // must never replace the current house/address.
            let release,startedResolve;const started=new Promise(r=>startedResolve=r);const gate=new Promise(r=>release=r);
            await page.route('**/resident/ajax?id=1',async route=>{startedResolve();await gate;await route.fulfill({status:200,contentType:'application/json',body:JSON.stringify(JSON.stringify({id:1,Codigo:'A-12',Direccion:'Casa A-12'}))});},{times:1});
            await select('C101');await started;await select('C202');await details('B-34','Casa B-34');
            const late=page.waitForResponse(r=>new URL(r.url()).pathname==='/resident/ajax'&&new URL(r.url()).searchParams.get('id')==='1');
            release();await (await late).finished();await page.evaluate(()=>new Promise(r=>setTimeout(r,100)));
            await details('B-34','Casa B-34');
            console.log('PASS '+channel+': slow previous response cannot overwrite current selection');

            // A failed lookup clears old details and leaves a working form.
            await page.route('**/resident/ajax?id=1',route=>route.fulfill({status:503,contentType:'application/json',body:'{}'}),{times:1});
            await select('C101');await page.getByText(/No se pudieron cargar los datos de la casa/).waitFor();
            assert.equal(await page.inputValue('#ftelefono'),'');assert.equal(await page.inputValue('#direccion'),'');
            assert.equal(await page.locator('#guardar').isEnabled(),true);
            await select('C202');await details('B-34','Casa B-34');
            assert.doesNotMatch(await page.locator('#responset').textContent(),/No se pudieron cargar los datos de la casa/);
            console.log('PASS '+channel+': lookup failure clears stale details; next selection recovers');

            await page.evaluate(()=>{const select=document.querySelector('#selectResidente');select.value='';window.jQuery(select).trigger('change');});
            assert.equal(await page.inputValue('#ftelefono'),'');assert.equal(await page.inputValue('#direccion'),'');
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
            page.on('request',r=>{if(r.method()==='POST'&&new URL(r.url()).pathname==='/visit/save')posted=r.postData();});
            await Promise.all([page.waitForURL('**/visit/detailvisit/*'),page.click('#guardar')]);
            assert.match(posted,/name="residente"\r\n\r\n2\r\n/);
            assert.match(await page.locator('body').innerText(),/Segundo\s+Residente/);
            assert.deepEqual(errors,[]);
            console.log('PASS '+channel+': saved visit belongs to selected resident; no uncaught errors');
            await context.close();
        } finally {await browser.close();}
    }
})().catch(error=>{console.error(error.stack);process.exitCode=1;});
