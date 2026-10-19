// Real Livewire page behind a local HTTP proxy. Partial bodies exercise fetch's
// response.text() deadline; server state and checksums always come from Laravel.
const assert=require('node:assert/strict');
const http=require('node:http');
const target='http://localhost:18880';
const code=['12345678','C','JUAN','','PEREZ','','01/01/1990','01/01/2030','1234567890101','12345678','METADATO ADICIONAL QUE SUPERA VEINTE CARACTERES'].join('|');
module.exports=async function failures(browser){
    let scenario='normal',sessionCount=0,posts=[],held=[],proxyBase;
    const sockets=new Set();
    const server=http.createServer((req,res)=>{
        const chunks=[];req.on('data',chunk=>chunks.push(chunk));req.on('end',()=>{
            const body=Buffer.concat(chunks),session=req.url==='/visit/session';
            const livewire=req.url.startsWith('/livewire/message/vehicle-visit');
            const save=livewire&&JSON.parse(body).updates.some(u=>u.payload.method==='save');
            if(session)sessionCount++;
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
                    const bytes=Buffer.concat(parts),out=isText?Buffer.from(bytes.toString().split(target).join(proxyBase)):bytes;
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
        for(const test of ['session-headers','session-body','save-headers','save-body','csrf-once','csrf-twice','invalid','offline']){
            scenario='normal';sessionCount=0;posts=[];
            const context=await browser.newContext();const page=await context.newPage();const errors=[];
            page.on('pageerror',e=>{if(page.url().includes('/visit/'))errors.push(e.message);});
            await context.route('**/*',route=>route.request().url().startsWith(proxyBase)?route.continue():route.abort());
            await page.clock.install();
            await page.goto(proxyBase+'/login');await page.fill('[name=username]','admin');await page.fill('[name=password]','visits-test-only');
            await Promise.all([page.waitForURL('**/home'),page.click('button[type=submit]')]);
            await page.goto(proxyBase+'/visit/create');
            await page.selectOption('#selectResidente','1');
            await page.waitForFunction(()=>document.querySelector('#ftelefono').value==='A-12').catch(async error=>{console.error(await page.evaluate(()=>({url:location.href,alert:document.querySelector('#vehicle-transport-alert').hidden,state:document.querySelector('[data-vehicle-visit-component]').__livewire.data})));throw error;});
            await page.fill('#placa','NETTEST');await page.fill('#cono','1');await page.fill('#data',code);await page.press('#data','Enter');
            await page.waitForFunction(()=>document.querySelector('[id="1nombre"]').textContent==='JUAN');
            scenario=test;sessionCount=0;posts=[];
            if(test==='offline')await context.setOffline(true);
            await page.click('#guardar');
            if(test.startsWith('session-')){
                await page.waitForTimeout(100);await page.clock.fastForward(10100);
            }else if(test.startsWith('save-')){
                for(let i=0;i<100&&!posts.length;i++)await page.waitForTimeout(20);
                assert.equal(posts.length,1);
                // Give the real commit time to finish before fast-forwarding timers.
                if(test==='save-body')await page.waitForTimeout(300);
                await page.clock.fastForward(60100);
            }
            if(test==='csrf-once'){
                await page.waitForURL('**/visit/detailvisit/*');
                assert.equal(posts.length,2);assert.equal(posts[0],posts[1]);assert.equal(sessionCount,2);
            }else{
                await page.locator('#vehicle-transport-alert').waitFor({state:'visible'});
                assert.equal(await page.locator('#vehicle-visit-form').evaluate(el=>el.inert),true);
                const backup=await page.evaluate(()=>JSON.parse(sessionStorage.getItem('garita.vehicle-visit.v1.1')));
                assert.equal(backup.inputs.placa,'NETTEST');assert.ok(backup.token);
                if(test==='csrf-twice'){assert.equal(posts.length,2);assert.equal(sessionCount,2);assert.equal(posts[0],posts[1]);}
                if(test.startsWith('session-'))assert.equal(posts.length,0,'no save before session preflight');
                scenario='normal';await context.setOffline(false);
                await page.click('#vehicle-reconnect');
                if(test==='save-body')await page.waitForURL('**/visit/detailvisit/*');
                else{
                    await page.locator('#vehicle-transport-alert').waitFor({state:'hidden'});
                    assert.equal(await page.locator('#vehicle-visit-form').evaluate(el=>el.inert),false);
                    await Promise.all([page.waitForURL('**/visit/detailvisit/*'),page.click('#guardar')]);
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
