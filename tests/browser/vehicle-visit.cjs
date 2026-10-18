// Only run against docker-compose.visits-test.yml. All identities/photos are synthetic.
const assert=require('node:assert/strict');
const {chromium}=require(process.env.PLAYWRIGHT_MODULE || 'playwright-core');
const failures=require('./vehicle-visit-failures.cjs');
const base=process.env.VISITS_BASE_URL || 'http://localhost:18880';
const target=new URL(base);
if (!['localhost','127.0.0.1'].includes(target.hostname) || target.port!=='18880') throw new Error('Only the isolated test server on port 18880 is allowed.');
const channel=process.env.VISITS_BROWSER || 'edge';
const code=['12345678','C','JUAN','','PEREZ','','01/01/1990','01/01/2030','1234567890101','12345678',''].join('|');
async function fakeCamera(){
    const ids=['fake-license','fake-plate','fake-visitor'];
    const devices={enumerateDevices:async()=>ids.map(id=>({kind:'videoinput',deviceId:id,label:id})),getUserMedia:async()=>{
        const canvas=document.createElement('canvas');canvas.width=640;canvas.height=480;
        const ctx=canvas.getContext('2d');ctx.fillStyle='#eee';ctx.fillRect(0,0,640,480);ctx.fillStyle='#123';ctx.font='30px Arial';ctx.fillText('FOTO SINTETICA DE PRUEBA',15,200);
        const stream=canvas.captureStream(5);
        // Repaint so every browser produces frames even for a detached canvas.
        const timer=setInterval(()=>{ctx.fillRect(0,0,5,5);},100);
        const track=stream.getVideoTracks()[0];const stop=track.stop.bind(track);
        track.stop=()=>{clearInterval(timer);stop();};
        return stream;
    }};
    Object.defineProperty(navigator,'mediaDevices',{configurable:true,value:devices});
}
(async()=>{
    const browser=await chromium.launch({headless:true,channel:channel==='edge'?'msedge':channel==='chrome'?'chrome':undefined});
    let activePage;
    try {
        if(process.env.VISITS_SKIP_HTTP!=='1') await failures(browser);
        const context=await browser.newContext({viewport:{width:1366,height:1000}});
        await context.addInitScript(fakeCamera);
        const page=await context.newPage();activePage=page;
        const errors=[];page.on('pageerror',e=>{if(new URL(page.url()).pathname.startsWith('/visit/'))errors.push(e.stack);});
        await context.route('**/*',route=>{
            const url=route.request().url();return url.startsWith(base)||url.startsWith('blob:')||url.startsWith('data:')?route.continue():route.abort();
        });
        async function control(photos){
            const response=await context.request.post(base+'/_visits_test_control',{headers:{'X-Visits-Test':'isolated-visits-test'},data:photos?{photos}:{}});
            assert.equal(response.status(),200,await response.text());const result=await response.json();
            assert.equal(result.database,'garita_visits_test');assert.equal(result.storage,'local');assert.equal(result.test_key,true);assert.equal(result.test_cookie,true);return result;
        }
        const initial=await control();
        await page.goto(base+'/login');
        await page.fill('[name=username]','admin');await page.fill('[name=password]','visits-test-only');
        await Promise.all([page.waitForURL(url=>!url.pathname.includes('login')),page.click('button[type=submit]')]);
        async function modalReady(){
            await page.waitForFunction(()=>{const modal=window.jQuery('#modalimg').data('bs.modal');return modal&&!modal._isTransitioning;});
        }
        async function prepare(plate,manual,photos=[1,1,1],delivery=false){
            await control(photos);
            await page.goto(base+'/visit/create',{waitUntil:'domcontentloaded'});
            const script=await page.locator('script[src*="vehicle-visit.js"]').getAttribute('src');assert.match(script,/\?v=\d+/);
            await page.fill('#placa',plate);await page.fill('#cono','17');
            await page.selectOption('#selectResidente','1');
            if (delivery) await page.click('.switch label');
            if(manual){await page.click('#abrirIngresoManual');await modalReady();await page.fill('#nombre-manual','MANUAL '+plate);await page.click('#guardarManual');await page.locator('#modalimg').waitFor({state:'hidden'});}
            else{await page.fill('#data',code);await page.press('#data','Enter');}
            if(photos.some(Boolean)){
                await page.waitForFunction(()=>!document.querySelector('#capture-btn').disabled,null,{timeout:10000}).catch(async error=>{
                    throw new Error(error.message+' '+JSON.stringify(await page.evaluate(()=>({message:document.querySelector('#responset').textContent,videos:[1,2,3].map(n=>{const v=document.querySelector('#video'+n);return {ready:v.readyState,width:v.videoWidth,stream:!!v.srcObject,paused:v.paused};})}))));
                });
                await page.click('#capture-btn');
            }
        }
        async function save(){await Promise.all([page.waitForURL('**/visit/detailvisit/*'),page.click('#guardar')]);return Number(new URL(page.url()).pathname.split('/').pop());}
        async function detail(id,manual,photos,delivery){
            const state=await control();const visit=state.visits.find(v=>Number(v.id)===id);assert.ok(visit);
            assert.equal(Number(visit.es_repartidor),Number(delivery));
            for(const [i,column] of ['path_licencia','path_placa','path_visitante'].entries()){
                assert.equal(!!visit[column],!!photos[i]&&!(manual&&i===0));
                if(visit[column]){
                    const response=await context.request.get(base+'/visit/imagen?filename='+encodeURIComponent(visit[column]));
                    assert.equal(response.status(),200);assert.match(response.headers()['content-type'],/^image\//);assert.ok((await response.body()).length>100);
                }
            }
            await page.waitForFunction(()=>Array.from(document.querySelectorAll('img[alt^="Foto"]')).every(image=>image.complete&&image.naturalWidth>0));
            await page.goto(base+'/visit/detail');
            await page.waitForFunction(plate=>document.body.innerText.includes(plate),visit.Placa);
            await Promise.all([page.waitForURL('**/visit/detailegreso/'+id),page.locator('a[title="Salida Visitante"][href$="/'+id+'"]').click()]);
            await Promise.all([
                page.waitForResponse(r=>r.request().method()==='POST'&&new URL(r.url()).pathname==='/visit/egreso'),
                page.locator('form[action$="/visit/egreso"] button[type=submit]').click()
            ]);
            assert.match(await page.locator('body').innerText(),/Egreso|egreso/);
            assert.ok((await control()).visits.find(v=>Number(v.id)===id).fechaegreso);
        }
        await control([1,1,1]);
        await page.goto(base+'/visit/create');
        await page.click('#guardar');
        await page.getByText('Revise estos datos antes de guardar:').waitFor();
        assert.equal(await page.locator('#guardar').isEnabled(),true);
        await page.click('#abrirIngresoManual');
        await page.fill('#nombre-manual','BORRADOR SIN CONFIRMAR');
        await page.waitForFunction(()=>{
            const key='garita.vehicle-visit.v1.'+document.querySelector('#vehicle-visit-form').dataset.userId;
            return JSON.parse(sessionStorage.getItem(key)||'null')?.manual_name_input==='BORRADOR SIN CONFIRMAR';
        });
        await page.reload();
        await page.click('#abrirIngresoManual');
        assert.equal(await page.inputValue('#nombre-manual'),'BORRADOR SIN CONFIRMAR');
        await modalReady();
        await page.locator('#modalimg [data-dismiss="modal"]').click();
        await page.locator('#modalimg').waitFor({state:'hidden'});
        await page.click('#prueba');
        console.log('PASS E2E: validation and manual name outside the form survive draft reload');
        let saved=0;
        for(let mask=0;mask<8;mask++){
            const photos=[mask&1?1:0,mask&2?1:0,mask&4?1:0];const manual=mask%2===0;const delivery=mask%3===0;
            await prepare('C'+(channel==='edge'?'E':'C')+String(mask).padStart(5,'0'),manual,photos,delivery);
            const id=await save();saved++;await detail(id,manual,photos,delivery);console.log('PASS E2E camera mask: '+mask);
        }
        console.log('PASS E2E: eight camera configurations, manual/scanner, delivery, photos, list and exit');
        for(let i=0;i<20;i++){
            const manual=i%2===0,delivery=i%3===0;
            await prepare('S'+(channel==='edge'?'E':'C')+String(i).padStart(5,'0'),manual,[1,1,1],delivery);
            const id=await save();saved++;await detail(id,manual,[1,1,1],delivery);
        }
        console.log('PASS E2E: 20 consecutive complete vehicle registration/exit cycles');
        // A response lost AFTER the real server committed must recover the same row/photos.
        await prepare('L'+(channel==='edge'?'E':'C')+'000001',false);
        const uuid=await page.locator('#vehicle-visit-form').getAttribute('data-submission-id');
        await page.route('**/visit/save',async route=>{const result=await route.fetch();assert.equal(result.status(),200);await route.abort('failed');},{times:1});
        await page.click('#guardar');await page.getByText(/interrumpió la conexión durante el guardado/).waitFor();
        await page.reload();await page.waitForFunction(()=>document.querySelector('#placa').disabled);
        const id=await save();saved++;await detail(id,false,[1,1,1],false);
        assert.equal((await control()).visits.filter(v=>v.submission_id===uuid).length,1);
        console.log('PASS E2E: lost committed response, reload, same UUID and preserved photos');
        // Concurrent requests and a duplicate submit event exercise server and UI separately.
        await prepare('D'+(channel==='edge'?'E':'C')+'000001',false);
        const stateBefore=await control();
        const payload=await page.evaluate(()=>{
            const f=document.querySelector('#vehicle-visit-form');return {submission_id:f.dataset.submissionId,residente:'1',placa:document.querySelector('#placa').value,cono:'17',tokens:['12345678','C','JUAN','','PEREZ','','01/01/1990','01/01/2030','1234567890101','12345678',''],photo1:window.photo1,photo2:window.photo2,photo3:window.photo3};
        });
        const session=await context.request.get(base+'/visit/session');const token=(await session.json()).csrf_token;
        const post=()=>context.request.post(base+'/visit/save',{headers:{Accept:'application/json','X-CSRF-TOKEN':token},data:payload});
        const replies=await Promise.all([post(),post()]);
        for(const reply of replies)assert.equal(reply.status(),200,await reply.text());
        const ids=await Promise.all(replies.map(r=>r.json()));assert.equal(ids[0].id,ids[1].id);
        let requestCount=0;const count=r=>{if(r.method()==='POST'&&new URL(r.url()).pathname==='/visit/save')requestCount++;};page.on('request',count);
        await page.evaluate(()=>{const f=document.querySelector('#vehicle-visit-form');f.dispatchEvent(new Event('submit',{cancelable:true}));f.dispatchEvent(new Event('submit',{cancelable:true}));});
        await page.waitForURL('**/visit/detailvisit/*');assert.equal(requestCount,1);saved++;
        const after=await control();assert.equal(after.visits.length,stateBefore.visits.length+1);assert.equal(after.visit_logs,stateBefore.visit_logs+1);
        await detail(ids[0].id,false,[1,1,1],false);
        const final=await control();assert.equal(final.visits.length,initial.visits.length+saved);assert.equal(final.visit_logs,initial.visit_logs+saved);
        assert.ok(final.visits.every(v=>v.fechaegreso), 'all created visits completed their exit');
        const referenced=final.visits.flatMap(v=>[v.path_licencia,v.path_placa,v.path_visitante]).filter(Boolean).sort();
        assert.deepEqual(final.files.slice().sort(),referenced,'no missing or orphaned photos after concurrent saves');
        assert.deepEqual(errors,[]);
        console.log('PASS E2E: concurrent saves and duplicate click produce one visit/log; no uncaught visit-page errors');
        console.log(JSON.stringify({browser:channel,completeCycles:saved,newVisits:final.visits.length-initial.visits.length,newVisitLogs:final.visit_logs-initial.visit_logs,photos:final.files.length,uncaughtErrors:errors.length}));
        await context.close();
    }catch(error){
        if(activePage&&!activePage.isClosed()) {
            const diagnostic=await activePage.evaluate(()=>({
                url:location.href, message:document.querySelector('#responset')?.textContent,
                button:document.querySelector('#guardar')?.textContent,
                plate:document.querySelector('#placa')?.value, modal:document.querySelector('#modalimg')?.className,
                videos:[1,2,3].map(n=>{const v=document.querySelector('#video'+n);return v&&{width:v.videoWidth,paused:v.paused,hidden:v.hidden,ready:v.readyState};})
            })).catch(()=>({diagnostic:'Page unavailable'}));
            console.error(JSON.stringify(diagnostic));
        }
        throw error;
    }finally{await browser.close();}
})().catch(error=>{console.error(error.stack);process.exitCode=1;});
