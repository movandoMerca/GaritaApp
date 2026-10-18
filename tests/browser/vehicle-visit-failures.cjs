// Real HTTP streaming failures; uses the actual vehicle JS, no database or S3.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const http = require('node:http');
const source = fs.readFileSync(path.join(__dirname, '../../public/js/vehicle-visit.js'), 'utf8');
const labels = ['iniciales','1nombre','2nombre','1apellido','2apellido','icon','ocultar','fechanac','cui','telefonoEmergencia','tipo','ndocument','fechavec','horaentrada'];
const html = `<!doctype html><meta charset="utf-8"><form id="vehicle-visit-form" data-save-url="/visit/save" data-session-url="/visit/session" data-resident-url="/resident/ajax" data-detail-template="/visit/detailvisit/__VISIT_ID__" data-user-id="1" data-submission-id="12345678-1234-4234-9234-123456789abc" data-csrf-token="test" data-login-url="/login" data-photo-licencia="false" data-photo-placa="false" data-photo-visitante="false"><div id="responset" hidden></div><input id="data" class="visit-input"><select id="selectResidente" class="visit-input"><option value="1">Casa ficticia</option></select><input id="placa" class="visit-input"><input id="cono" class="visit-input"><input id="es_repartidor" type="checkbox" class="visit-input"><input id="nombre-manual" class="visit-input"><input id="ftelefono"><input id="direccion">${labels.map(id=>`<div id="${id}"></div>`).join('')}${[1,2,3].map(n=>`<video id="video${n}"></video><canvas id="canvas${n}"></canvas>`).join('')}<button type="submit" id="guardar">Guardar</button><button type="button" id="guardarManual">Manual</button><button type="button" id="abrirIngresoManual">Abrir</button><button type="button" id="capture-btn">Fotos</button><a id="prueba" href="/cancel">Cancelar</a><div id="modalimg"></div></form><script src="/vehicle-visit.js"></script>`;
async function until(predicate) {
    for (let i = 0; i < 500; i++) { if (predicate()) return; await new Promise(r=>setTimeout(r, 10)); }
    throw new Error('HTTP test stage did not arrive');
}
module.exports = async function failures(browser) {
    let scenario, sessions, posts, bodies, held;
    const sockets = new Set();
    const json = (res,status,value) => {res.writeHead(status,{'Content-Type':'application/json'});res.end(JSON.stringify(value));};
    const session = res => json(res,200,{user_id:1,csrf_token:'test'});
    const server = http.createServer((req,res)=>{
        if (req.url === '/fixture') {res.writeHead(200,{'Content-Type':'text/html; charset=utf-8'});res.end(html);return;}
        if (req.url === '/vehicle-visit.js') {res.writeHead(200,{'Content-Type':'text/javascript; charset=utf-8'});res.end(source);return;}
        if (req.url.startsWith('/resident/ajax')) {json(res,200,{Codigo:'TEST',Direccion:'FICTICIA'});return;}
        if (req.url === '/visit/session') {
            sessions++;
            if (scenario === 'session-headers') return;
            if (scenario === 'session-body' || (scenario === 'csrf-renew' && sessions === 2)) {
                res.writeHead(200,{'Content-Type':'application/json'});res.write('{"user_id":');return;
            }
            if (scenario === 'account') {json(res,200,{user_id:2,csrf_token:'test'});return;}
            if (scenario === 'expired') {json(res,401,{message:'Unauthenticated'});return;}
            if (scenario === 'budget' && sessions === 2) {held = res;return;}
            session(res);return;
        }
        if (req.url === '/visit/save') {
            posts++;
            let body='';req.on('data',chunk=>body+=chunk);
            req.on('end',()=>{
                bodies.push(body);
                if (scenario.startsWith('csrf-') && posts === 1) {json(res,419,{message:'CSRF'});return;}
                if (scenario === 'save-headers') return;
                if (['save-body','csrf-body'].includes(scenario)) {
                    res.writeHead(200,{'Content-Type':'application/json'});res.write('{"id":');return;
                }
                if (scenario === 'budget') {held = res;return;}
                if (scenario === 'csrf-lost') {req.socket.destroy();return;}
                if (scenario === 'invalid') {res.writeHead(200,{'Content-Type':'application/json'});res.end('{"id":');return;}
                if (/^status-/.test(scenario)) {
                    const status=Number(scenario.split('-')[1]);
                    json(res,status,{message:'Prueba '+status,outcome:status===503?'unknown':undefined});return;
                }
                if (scenario === 'not-saved') {json(res,503,{message:'Fallo confirmado',outcome:'not_saved'});return;}
                json(res,200,{id:7});
            });return;
        }
        res.end('Detalle de visita de prueba');
    });
    server.on('connection',socket=>{sockets.add(socket);socket.on('close',()=>sockets.delete(socket));});
    await new Promise(r=>server.listen(0,'127.0.0.1',r));
    const base='http://127.0.0.1:'+server.address().port;
    try {
        const cases = ['session-headers','session-body','save-headers','save-body','csrf-renew','csrf-body','csrf-lost','budget','expired','account','invalid','status-401','status-403','status-413','status-419','status-422','status-409','status-503','not-saved','storage-disabled'];
        for (scenario of cases) {
            sessions=posts=0;bodies=[];held=null;
            const context=await browser.newContext();
            const page=await context.newPage();
            const errors=[];page.on('pageerror',e=>errors.push(e.message));
            await page.clock.install();
            if (scenario === 'storage-disabled') await page.addInitScript(()=>{
                Storage.prototype.setItem=function(){throw new DOMException('Full','QuotaExceededError');};
            });
            try {
                await page.goto(base+'/fixture');
                await page.fill('#nombre-manual','VISITANTE DE PRUEBA');await page.click('#guardarManual');
                await page.fill('#placa','TEST001');await page.fill('#cono','1');await page.click('#guardar');
                if (scenario === 'budget') {
                    await until(()=>posts===1 && held);
                    await page.clock.fastForward(45000);
                    json(held,419,{message:'CSRF'});held=null;
                    await until(()=>sessions===2 && held);
                    await page.clock.fastForward(5000);
                    session(held);held=null;
                    await until(()=>posts===2 && held);
                    await page.clock.fastForward(10050);
                } else if (scenario.includes('headers') || scenario.includes('body') || scenario === 'csrf-renew') {
                    await until(()=>scenario.startsWith('session') ? sessions>0 : (scenario==='csrf-renew' ? sessions===2 : posts>=(scenario==='csrf-body'?2:1)));
                    // Let the headers/body reach fetch before advancing its actual timers.
                    await new Promise(r=>setTimeout(r,50));
                    await page.clock.fastForward(61000);
                }
                if (scenario === 'storage-disabled') {
                    await page.waitForURL('**/visit/detailvisit/7');
                } else {
                    await page.waitForFunction(()=>!document.querySelector('#guardar').disabled);
                    const uncertain=['save-headers','save-body','csrf-body','csrf-lost','budget','invalid','status-409','status-503'].includes(scenario);
                    assert.equal(await page.locator('#placa').isDisabled(),uncertain,scenario);
                    assert.equal(await page.locator('#responset').isVisible(),true,scenario);
                    assert.equal(await page.inputValue('#placa'),'TEST001');
                    assert.doesNotMatch(await page.locator('#guardar').innerText(),/Guardando/);
                    if (uncertain) {
                        const draft=await page.evaluate(()=>JSON.parse(sessionStorage.getItem('garita.vehicle-visit.v1.1')));
                        assert.equal(draft.attempt_sent,true);
                        await page.reload();
                        await page.waitForFunction(()=>document.querySelector('#placa').disabled);
                        assert.equal(await page.inputValue('#placa'),'TEST001');
                        assert.equal(await page.locator('#guardar').isEnabled(),true);
                    }
                }
                if (scenario==='account' || scenario==='expired' || scenario.startsWith('session')) assert.equal(posts,0);
                assert.deepEqual(errors,[],scenario);
                console.log('PASS HTTP/browser: '+scenario);
            } finally {await context.close();for(const socket of sockets)socket.destroy();}
        }
    } finally {for(const socket of sockets)socket.destroy();await new Promise(r=>server.close(r));}
};
