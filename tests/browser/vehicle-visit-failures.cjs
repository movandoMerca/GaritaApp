// Real Livewire page behind a local HTTP proxy. Partial bodies exercise fetch's
// response.text() deadline; server state and checksums always come from Laravel.
const assert=require('node:assert/strict');
const http=require('node:http');
const target='http://localhost:18880';
const code=['12345678','C','JUAN','','PEREZ','','01/01/1990','01/01/2030','1234567890101','12345678','METADATO ADICIONAL QUE SUPERA VEINTE CARACTERES'].join('|');
function fakeCamera(){
    const ids=['fake-license'];
    Object.defineProperty(navigator,'mediaDevices',{configurable:true,value:{
        enumerateDevices:async()=>ids.map(id=>({kind:'videoinput',deviceId:id,label:id})),
        getUserMedia:async()=>{
            const canvas=document.createElement('canvas');canvas.width=640;canvas.height=480;
            const context=canvas.getContext('2d');context.fillStyle='#eee';context.fillRect(0,0,640,480);
            context.fillStyle='#123';context.font='30px Arial';context.fillText('FOTO SINTETICA DE PRUEBA',15,200);
            const stream=canvas.captureStream(5);
            const timer=setInterval(()=>context.fillRect(0,0,5,5),100);
            const track=stream.getVideoTracks()[0],stop=track.stop.bind(track);
            track.stop=()=>{clearInterval(timer);stop();};
            return stream;
        },
    }});
}
module.exports=async function failures(browser){
    let scenario='normal',sessionCount=0,posts=[],clientLogs=[],held=[],proxyBase;
    const sockets=new Set();
    const server=http.createServer((req,res)=>{
        const chunks=[];req.on('data',chunk=>chunks.push(chunk));req.on('end',()=>{
            const body=Buffer.concat(chunks),session=req.method==='GET'&&req.url==='/visit/session';
            const systemLog=req.method==='POST'&&req.url==='/visit/session';
            const livewire=req.url.startsWith('/livewire/message/vehicle-visit');
            const save=livewire&&JSON.parse(body).updates.some(u=>u.payload.method==='save');
            if(session)sessionCount++;
            if(systemLog){try{clientLogs.push(JSON.parse(body).events||[]);}catch(_) {}}
            if(save)posts.push(body.toString());
            if(session&&scenario==='session-headers'){held.push(res);return;}
            if(session&&scenario==='session-body'){res.writeHead(200,{'Content-Type':'application/json'});res.write('{');held.push(res);return;}
            if(save&&scenario==='save-headers'){held.push(res);return;}
            if(save&&scenario==='csrf-once'&&posts.length===1){res.writeHead(419);res.end('{}');return;}
            if(save&&scenario==='csrf-twice'){res.writeHead(419);res.end('{}');return;}
            if(save&&scenario==='invalid'){res.writeHead(200,{'Content-Type':'application/json'});res.end('{}');return;}
            const headers={...req.headers,host:'localhost:18880'};
            delete headers['accept-encoding'];delete headers.connection;
            const upstream=http.request(target+req.url,{method:req.method,headers},reply=>{
                const parts=[];reply.on('data',part=>parts.push(part));reply.on('end',()=>{
                    if(res.destroyed)return;
                    const responseHeaders={...reply.headers};delete responseHeaders['content-length'];delete responseHeaders['transfer-encoding'];delete responseHeaders.connection;
                    if(responseHeaders.location)responseHeaders.location=responseHeaders.location.split(target).join(proxyBase);
                    const isText=/json|html|javascript|css/.test(responseHeaders['content-type']||'');
                    if(livewire&&reply.statusCode!==200)console.error('proxy Livewire',reply.statusCode,Buffer.concat(parts).toString().slice(0,250));
                    const escapedTarget=target.replace(/\//g,'\\/'),escapedBase=proxyBase.replace(/\//g,'\\/');
                    const bytes=Buffer.concat(parts),rewritten=bytes.toString().split(target).join(proxyBase)
                        .split(escapedTarget).join(escapedBase),out=isText?Buffer.from(rewritten):bytes;
                    if(save&&scenario==='save-body'){res.writeHead(reply.statusCode,responseHeaders);res.write(out.subarray(0,20));held.push(res);return;}
                    res.writeHead(reply.statusCode,responseHeaders);res.end(out);
                });
            });upstream.on('error',()=>{res.writeHead(502);res.end('{}');});upstream.end(body);
        });
    });
    server.on('connection',socket=>{sockets.add(socket);socket.on('close',()=>sockets.delete(socket));});
    await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
    proxyBase='http://127.0.0.1:'+server.address().port;
    try{
        const control=await fetch(target+'/_visits_test_control',{method:'POST',headers:{'X-Visits-Test':'isolated-visits-test','Content-Type':'application/json'},body:JSON.stringify({photos:[0,0,0]})});
        assert.equal(control.status,200);assert.equal((await control.json()).database,'garita_visits_test');
        for(const test of ['idle-session','expired-session','logged-out','account-changed','session-headers','session-body','save-headers','save-body','csrf-once','csrf-twice','invalid','offline','photo-upload']){
            scenario='normal';sessionCount=0;posts=[];clientLogs=[];
            const context=await browser.newContext();const page=await context.newPage();const errors=[];
            let uploadAttempts=0,resolveFirstUpload,resolveSecondUpload;
            const firstUpload=new Promise(resolve=>{resolveFirstUpload=resolve;}),secondUpload=new Promise(resolve=>{resolveSecondUpload=resolve;});
            page.on('pageerror',e=>{if(page.url().includes('/visit/'))errors.push(e.message);});
            await context.route('**/*',route=>route.request().url().startsWith(proxyBase)?route.continue():route.abort());
            await page.clock.install();
            await page.goto(proxyBase+'/login');assert.equal(await page.isChecked('#remember'),true);
            await page.fill('[name=username]','admin');await page.fill('[name=password]','visits-test-only');
            await Promise.all([page.waitForURL('**/home'),page.click('button[type=submit]')]);
            assert.ok((await context.cookies(proxyBase)).some(cookie=>cookie.name.startsWith('remember_web_')),'login remembers the browser by default');
            if(test==='photo-upload'){
                const enabled=await context.request.post(target+'/_visits_test_control',{headers:{'X-Visits-Test':'isolated-visits-test'},data:{photos:[1,0,0]}});
                assert.equal(enabled.status(),200);
                await context.addInitScript(fakeCamera);
            }
            await page.goto(proxyBase+'/visit/create');
            if(test==='photo-upload') await page.route('**/livewire/upload-file**',async route=>{
                uploadAttempts++;
                if(uploadAttempts===1){await route.fulfill({status:500,contentType:'application/json',body:'{}'});resolveFirstUpload();return;}
                if(uploadAttempts===2)resolveSecondUpload();
                await route.continue();
            });
            await page.selectOption('#selectResidente','1',{force:true});
            await page.waitForFunction(()=>document.querySelector('#ftelefono').value==='A-12').catch(async error=>{console.error(await page.evaluate(()=>({url:location.href,state:document.querySelector('[data-vehicle-visit-component]').__livewire.data})));throw error;});
            await page.fill('#placa','NETTEST');await page.fill('#cono','1');await page.fill('#data',code);await page.press('#data','Enter');
            await page.waitForFunction(()=>document.querySelector('[id="1nombre"]').textContent==='JUAN',{},{timeout:10000}).catch(async error=>{
                console.error(await page.evaluate(()=>{
                    const c=document.querySelector('[data-vehicle-visit-component]').__livewire;
                    return {notice:document.querySelector('#responset').textContent,code:document.querySelector('#data').value,
                        model:c.data.codigoLicencia,visitor:c.data.visitor,status:c.data.status,
                        inert:document.querySelector('#vehicle-visit-form').inert,
                        sessionUrl:document.querySelector('#vehicle-visit-form').dataset.sessionUrl,
                        livewireUrl:document.querySelector('#vehicle-visit-form').dataset.livewireUrl,
                        inTransit:c.messageInTransit?.updateQueue?.map(update=>({type:update.type,name:update.payload?.name,method:update.payload?.method})),
                        updates:c.updateQueue.map(update=>({type:update.type,name:update.payload?.name,method:update.payload?.method}))};
                }),errors);
                throw error;
            });
            scenario=test;sessionCount=0;posts=[];
            if(test==='offline')await context.setOffline(true);
            if(test==='idle-session'){
                await page.clock.fastForward(60000);
                for(let i=0;i<100&&!sessionCount;i++)await page.waitForTimeout(20);
                assert.ok(sessionCount>0,'the idle form keeps its session alive');
            }
            if(test==='expired-session'){
                const cookies=await context.cookies(proxyBase);
                assert.ok(cookies.some(cookie=>cookie.name==='garita_visits_test_session'));
                assert.ok(cookies.some(cookie=>cookie.name.startsWith('remember_web_')));
                await context.clearCookies({name:'garita_visits_test_session'});
                assert.ok((await context.cookies(proxyBase)).some(cookie=>cookie.name.startsWith('remember_web_')),
                    'the seven-day remembered login survives expiration of the ordinary session');
            }
            if(test==='logged-out'){
                const token=await page.evaluate(()=>window.livewire_token);
                const response=await context.request.post(proxyBase+'/logout',{
                    headers:{Accept:'application/json','X-CSRF-TOKEN':token},
                });
                assert.equal(response.status(),204);
                assert.equal((await context.cookies(proxyBase)).some(cookie=>cookie.name.startsWith('remember_web_')),false,
                    'logout clears the remembered login');
            }
            if(test==='account-changed'){
                await page.route('**/visit/session',route=>route.request().method()==='GET'
                    ? route.fulfill({status:200,contentType:'application/json',body:JSON.stringify({user_id:2,csrf_token:'another-user-token'})})
                    : route.continue());
            }
            if(test==='photo-upload'){
                await Promise.race([firstUpload,new Promise((_,reject)=>setTimeout(()=>reject(new Error('photo upload did not start')),15000))]);
                await new Promise(resolve=>setTimeout(resolve,50));
                await page.clock.fastForward(1000);
                await Promise.race([secondUpload,new Promise((_,reject)=>setTimeout(()=>reject(new Error('photo upload was not retried')),15000))]);
                await page.waitForFunction(()=>{
                    const component=document.querySelector('[data-vehicle-visit-component]').__livewire;
                    return component.data.photoVersions.licencia===component.data.captureGeneration
                        &&component.data.capturePending.length===0&&!component.messageInTransit&&!component.updateQueue.length;
                });
                assert.equal(uploadAttempts,2,'the same photo is retried once after a transient upload failure');
            }
            const submissionId=test==='photo-upload'
                ? await page.locator('#vehicle-visit-form').getAttribute('data-submission-id')
                : null;
            await page.click('#guardar');
            if(test==='photo-upload'){
                await page.waitForURL('**/visit/detailvisit/*');
                assert.equal(posts.length,1);
                const state=await (await context.request.post(target+'/_visits_test_control',{headers:{'X-Visits-Test':'isolated-visits-test'},data:{}})).json();
                const visit=state.visits.find(item=>item.submission_id===submissionId);
                assert.ok(visit?.path_licencia,'the retried photo is attached to the saved visit');
            }else if(test.startsWith('session-')){
                await page.waitForTimeout(100);await page.clock.fastForward(10100);
            }else if(test.startsWith('save-')){
                for(let i=0;i<100&&!posts.length;i++)await page.waitForTimeout(20);
                assert.equal(posts.length,1);
                // Give the real commit time to finish before fast-forwarding timers.
                if(test==='save-body')await page.waitForTimeout(300);
                await page.clock.fastForward(60100);
            }else if(test==='csrf-twice'){
                for(let i=0;i<100&&posts.length<2;i++)await page.waitForTimeout(20);
                assert.equal(posts.length,2);
            }
            if(test==='csrf-once'){
                await page.waitForURL('**/visit/detailvisit/*');
                assert.equal(posts.length,2);assert.equal(posts[0],posts[1]);assert.equal(sessionCount,2);
            }else if(test==='idle-session'||test==='expired-session'){
                await page.waitForURL('**/visit/detailvisit/*');
                assert.equal(posts.length,1);
                assert.equal(await page.locator('#vehicle-transport-alert').count(),0,'the transport banner was removed');
                if(test==='expired-session')assert.ok((await context.cookies(proxyBase))
                    .some(cookie=>cookie.name==='garita_visits_test_session'),'a fresh session is created from the remembered login');
            }else if(test==='logged-out'){
                await page.waitForFunction(()=>document.querySelector('#vehicle-visit-form').inert);
                assert.equal(posts.length,0,'a logged-out request is never submitted');
                assert.equal(await page.inputValue('#placa'),'NETTEST');
                assert.equal((await page.locator('#responset').innerText()).trim(),'','session failures are not shown to the operator');
                assert.equal(await page.locator('#vehicle-transport-alert').count(),0,'the transport banner was removed');
            }else if(test==='account-changed'){
                await page.waitForFunction(()=>document.querySelector('#vehicle-visit-form').inert);
                assert.equal(posts.length,0,'the existing form is never submitted as another user');
                assert.equal(await page.inputValue('#placa'),'NETTEST');
                assert.equal((await page.locator('#responset').innerText()).trim(),'','account changes are not shown to the operator');
                assert.equal(await page.locator('#vehicle-transport-alert').count(),0,'the transport banner was removed');
            }else if(test==='photo-upload'){
                assert.equal(await page.locator('#vehicle-transport-alert').count(),0,'the transport banner was removed');
                for(let i=0;i<100&&!clientLogs.length;i++)await new Promise(resolve=>setTimeout(resolve,20));
                assert.ok(clientLogs.flat().some(event=>event.operation==='photo-upload'&&event.event==='request-failed'),
                    'the upload failure is recorded in the application log');
            }else{
                if(test==='offline'){
                    await page.clock.fastForward(10);
                }
                await page.waitForFunction(()=>document.querySelector('#vehicle-visit-form').inert);
                assert.equal(await page.locator('#vehicle-visit-form').evaluate(el=>el.inert),true);
                assert.equal(await page.inputValue('#placa'),'NETTEST');
                assert.equal(await page.evaluate(()=>sessionStorage.getItem('garita.vehicle-visit.v1.1')),null);
                if(test.startsWith('session-'))assert.equal(posts.length,0,'no save before session preflight');
                assert.equal((await page.locator('#responset').innerText()).trim(),'','transport failures are not shown to the operator');
                assert.equal(await page.locator('#vehicle-transport-alert').count(),0,'the transport banner was removed');
                scenario='normal';held.forEach(res=>res.destroy());held=[];
                await context.setOffline(false);
                if(test!=='offline')await page.clock.fastForward(31000);
                await page.waitForURL('**/visit/detailvisit/*');
                if(test==='offline'){
                    for(let i=0;i<100&&!clientLogs.length;i++)await new Promise(resolve=>setTimeout(resolve,20));
                    assert.ok(clientLogs.flat().some(event=>event.operation==='save'&&event.event==='offline'),
                        'the offline save is recorded after the connection returns');
                }
                assert.equal(await page.evaluate(()=>sessionStorage.getItem('garita.vehicle-visit.v1.1')),null);
            }
            assert.deepEqual(errors,[]);console.log('PASS native HTTP: '+test);
            held.forEach(res=>res.destroy());held=[];await context.close();
        }
    }finally{held.forEach(res=>res.destroy());sockets.forEach(socket=>socket.destroy());await new Promise(resolve=>server.close(resolve));}
};
if(require.main===module){
    const {chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright-core');
    (async()=>{for(const channel of ['msedge','chrome']){const browser=await chromium.launch({channel,headless:true});try{await module.exports(browser);}finally{await browser.close();}}})().catch(error=>{console.error(error.stack);process.exitCode=1;});
}
