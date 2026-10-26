// Requires the isolated preview from Etapa 8 at :19118 and Chrome CDP at :19398.
// Run from repository root: node <this-file> <evidence-directory>
import { visualReviewOutput } from '../../../../scripts/visual-review-output.mjs';
import fs from 'node:fs';
const out = visualReviewOutput('2026-10-25-controls-40px', '', process.argv[2]);
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


import {execFileSync} from 'node:child_process';
await send('Emulation.setDeviceMetricsOverride',{width:375,height:900,deviceScaleFactor:1,mobile:false});
await send('Page.navigate',{url:'http://127.0.0.1:19119/admin/pedidos?scenario=admin'});
for(let n=0;n<150;n++){if(await evaluate('document.readyState==="complete" && !!document.querySelector(".storefront-header")'))break;await new Promise(r=>setTimeout(r,100));}
await evaluate('document.fonts.ready.then(()=>true)');
const measure=()=>evaluate('({client:document.documentElement.clientWidth,scroll:document.documentElement.scrollWidth,fields:[...document.querySelectorAll("main input,main select")].filter(e=>e.getClientRects().length).map(e=>{const r=e.getBoundingClientRect();return {width:r.width,height:r.height,right:r.right}})})');
const current=await measure();
const css=execFileSync('git',['show','HEAD:public/build/assets/app-CHcXdBQL.css'],{encoding:'utf8'});
await evaluate('document.querySelectorAll("link").forEach(e=>{if(e.href.includes("/build/assets/"))e.remove()}); const baselineStyle=document.createElement("style");baselineStyle.textContent='+JSON.stringify(css)+'; document.head.append(baselineStyle)');
const baseline=await measure();
const result={route:'/admin/pedidos',width:375,current,baseline,baselineSource:'HEAD compiled CSS; original administrative raw field paint/width, without geometry opt-in rules'};
fs.writeFileSync(out+'/admin-orders-baseline.json',JSON.stringify(result,null,2));console.log(JSON.stringify(result));ws.close();
