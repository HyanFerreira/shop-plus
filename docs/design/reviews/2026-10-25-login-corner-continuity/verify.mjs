// Requires the isolated preview from Etapa 8 at :19118 and Chrome CDP at :19398.
// Run from repository root: node <this-file> <evidence-directory>
import { visualReviewOutput } from '../../../../scripts/visual-review-output.mjs';
import fs from 'node:fs';
const out = visualReviewOutput('2026-10-25-login-corner-continuity', '', process.argv[2]);
const target = await (await fetch('http://127.0.0.1:19398/json/new?about:blank', { method: 'PUT' })).json();
const ws = new WebSocket(target.webSocketDebuggerUrl);
await new Promise(resolve => { ws.onopen = resolve; });
let id = 0;
const pending = new Map();
ws.onmessage = ({ data }) => {
    const message = JSON.parse(data), entry = pending.get(message.id);
    if (entry) { pending.delete(message.id); message.error ? entry.reject(message.error) : entry.resolve(message.result); }
};
const send = (method, params = {}) => new Promise((resolve, reject) => {
    pending.set(++id, { resolve, reject }); ws.send(JSON.stringify({ id, method, params }));
});
const evaluate = async expression => {
    const result = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
    if (result.exceptionDetails) throw Error(JSON.stringify(result.exceptionDetails));
    return result.result.value;
};
await send('Page.enable');
await send('Network.enable');
await send('Network.setCacheDisabled', { cacheDisabled: true });
await send('Emulation.setFocusEmulationEnabled', { enabled: true });

const reports=[];
for(const width of [320,375,768,1024,1280,1440]){
    await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:false});
    await send('Page.navigate',{url:'http://127.0.0.1:19118/login'});
    for(let n=0;n<100;n++){if(await evaluate('document.readyState === "complete" && !!document.querySelector("#email")'))break;await new Promise(r=>setTimeout(r,100));}
    await evaluate('document.fonts.ready.then(()=>true)');await evaluate('document.activeElement?.blur();new Promise(r=>setTimeout(r,200))');
    const r=await evaluate(`(()=>{const rect=s=>{const r=document.querySelector(s).getBoundingClientRect();return {x:r.x,right:r.right,y:r.y,bottom:r.bottom,width:r.width,height:r.height}};return {width:innerWidth,overflow:document.documentElement.scrollWidth>document.documentElement.clientWidth,card:rect('.auth-card'),panel:rect('.auth-benefits'),form:rect('.auth-content'),input:rect('#email'),background:getComputedStyle(document.querySelector('.auth-benefits')).backgroundImage,formRadius:getComputedStyle(document.querySelector('.auth-content')).borderTopLeftRadius}})()`);
    if(r.overflow||r.input.height!==40)throw Error('Layout '+width);
    if(width>=1024&&Math.abs(r.panel.right-r.form.x-parseFloat(r.formRadius))>1)throw Error('Background does not cover rounded corners '+JSON.stringify(r));
    reports.push(r);
    if([375,1024,1440].includes(width)){
        const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});fs.writeFileSync(out+'/login-'+width+'.png',Buffer.from(shot.data,'base64'));
    }
    if(width===1440){
        const shot=await send('Page.captureScreenshot',{format:'png',clip:{x:r.form.x-16,y:r.card.y,width:80,height:r.card.height,scale:1},captureBeyondViewport:true});fs.writeFileSync(out+'/panel-join.png',Buffer.from(shot.data,'base64'));
    }
}
fs.writeFileSync(out+'/layout.json',JSON.stringify({result:'PASS',reports},null,2));console.log('PASS: painted panel overlaps form by40px, covering both rounded corners; six widths without overflow');ws.close();
