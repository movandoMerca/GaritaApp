// Camera startup and scanner/manual transitions on the real Livewire page.
const assert = require('node:assert/strict');
const {chromium} = require(process.env.PLAYWRIGHT_MODULE || 'playwright-core');
const base = 'http://localhost:18880';
const code = ['12345678','C','JUAN','','PEREZ','','01/01/1990','01/01/2030','1234567890101','12345678',''].join('|');
(async () => {
    for (const channel of ['msedge','chrome']) {
        const browser = await chromium.launch({channel,headless:true});
        try {
            const context = await browser.newContext();
            await context.route('**/*',route=>route.request().url().startsWith(base)?route.continue():route.abort());
            await context.addInitScript(() => {
                window.photoCaptures = [];
                const serialize=HTMLCanvasElement.prototype.toDataURL;
                HTMLCanvasElement.prototype.toDataURL=function(...args){
                    if (/^canvas[123]$/.test(this.id)) window.photoCaptures.push(this.id);
                    return serialize.apply(this,args);
                };
                const gate=new Promise(resolve=>window.releaseTestCamera=resolve);
                Object.defineProperty(navigator,'mediaDevices',{configurable:true,value:{
                    enumerateDevices:async()=>[{kind:'videoinput',deviceId:'shared',label:'Shared synthetic camera'}],
                    getUserMedia:async constraints=>{
                        if (constraints.video?.deviceId) await gate;
                        const canvas=document.createElement('canvas');canvas.width=640;canvas.height=480;
                        const context=canvas.getContext('2d');context.fillStyle='#abc';context.fillRect(0,0,640,480);
                        const stream=canvas.captureStream(5);
                        const timer=setInterval(()=>context.fillRect(0,0,10,10),100);
                        const track=stream.getVideoTracks()[0],stop=track.stop.bind(track);
                        track.stop=()=>{clearInterval(timer);stop();};
                        return stream;
                    }
                }});
            });
            const check=await context.request.post(base+'/_visits_test_control',{headers:{'X-Visits-Test':'isolated-visits-test'},data:{photos:[1,1,1],cameras:['shared','shared','shared']}});
            assert.equal(check.status(),200);const initial=await check.json();assert.equal(initial.database,'garita_visits_test');
            const page=await context.newPage(),errors=[];
            page.on('pageerror',error=>{if(page.url().includes('/visit/'))errors.push(error.message);});
            await page.goto(base+'/login');await page.fill('[name=username]','admin');await page.fill('[name=password]','visits-test-only');
            await Promise.all([page.waitForURL(url=>!url.pathname.includes('/login')),page.click('button[type=submit]')]);
            await page.goto(base+'/visit/create');
            await page.fill('#data','INVALID CODE');await page.press('#data','Enter');
            await page.locator('#modalimg').waitFor({state:'visible'});
            assert.deepEqual(await page.evaluate(()=>photoCaptures),[]);
            await page.keyboard.press('Escape');await page.locator('#modalimg').waitFor({state:'hidden'});
            await page.fill('#data',code);await page.press('#data','Enter');
            assert.deepEqual(await page.evaluate(()=>photoCaptures),[]);
            // Replace a scan while camera initialization is pending. The pending
            // automatic capture must be cancelled by manual confirmation.
            await page.waitForFunction(()=>document.querySelector('[id="1nombre"]').textContent==='JUAN');
            await page.evaluate(()=>document.querySelector('[data-vehicle-visit-component]').__livewire.call('openManual'));
            await page.locator('#modalimg').waitFor({state:'visible'});
            await page.fill('#nombre-manual','MANUAL AFTER SCAN');await page.click('#guardarManual');
            await page.locator('#modalimg').waitFor({state:'hidden'});
            await page.evaluate(()=>releaseTestCamera());
            await page.waitForFunction(()=>[1,2,3].every(n=>document.querySelector('#video'+n).videoWidth>0));
            await page.waitForTimeout(250);
            assert.deepEqual(await page.evaluate(()=>photoCaptures),[],'manual confirmation cancels pending automatic photos');
            await page.click('#capture-btn');
            await page.waitForFunction(()=>photoCaptures.length===2);
            await page.waitForFunction(()=>{const c=document.querySelector('[data-vehicle-visit-component]').__livewire;return Object.keys(c.data.photoVersions).length===2&&!c.messageInTransit&&!c.updateQueue.length;});
            assert.deepEqual(await page.evaluate(()=>photoCaptures),['canvas2','canvas3'],'manual entry does not take a license photo');
            await page.fill('#data',code);await page.press('#data','Enter');
            await page.waitForFunction(()=>photoCaptures.length===5);
            assert.deepEqual(await page.evaluate(()=>photoCaptures.slice(2)),['canvas1','canvas2','canvas3']);
            await page.waitForFunction(()=>{const c=document.querySelector('[data-vehicle-visit-component]').__livewire;return Object.keys(c.data.photoVersions).length===3&&!c.messageInTransit&&!c.updateQueue.length;});
            await page.click('#capture-btn');
            await page.waitForFunction(()=>photoCaptures.length===8);
            await page.waitForFunction(()=>{const c=document.querySelector('[data-vehicle-visit-component]').__livewire;return Object.keys(c.data.photoVersions).length===3&&!c.messageInTransit&&!c.updateQueue.length;});
            assert.equal(await page.evaluate(()=>photoCaptures.length),8,'button can retake all three automatic photos');
            assert.doesNotMatch(await page.locator('#responset').textContent(),/capturad[ao]s/i);
            const response=await context.request.post(base+'/_visits_test_control',{headers:{'X-Visits-Test':'isolated-visits-test'},data:{}});
            const state=await response.json();
            assert.equal(state.visits.length,initial.visits.length,'taking photos does not save a visit');
            const referenced=state.visits.flatMap(visit=>[visit.path_licencia,visit.path_placa,visit.path_visitante]).filter(Boolean).sort();
            assert.deepEqual(state.files.slice().sort(),referenced,'no missing or orphaned photos after all browser suites');
            assert.deepEqual(errors,[]);
            console.log('PASS '+channel+': invalid scan, pending capture cancellation, manual photos, automatic license photos and retake');
            console.log(JSON.stringify({browser:channel,visits:state.visits.length,logs:state.visit_logs,photos:state.files.length}));
            await context.close();
        } finally {await browser.close();}
    }
})().catch(error=>{console.error(error.stack);process.exitCode=1;});
