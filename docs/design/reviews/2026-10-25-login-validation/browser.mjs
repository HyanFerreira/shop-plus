// Requires the isolated preview from Etapa 8 at :19118 and Chrome CDP at :19398.
// Run from repository root: node <this-file> <evidence-directory>
import { visualReviewOutput } from '../../../../scripts/visual-review-output.mjs';
import fs from 'node:fs';
const out = visualReviewOutput('2026-10-25-login-validation', '', process.argv[2]);
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

const assert=(ok,message)=>{if(!ok)throw Error(message)};
const nav=async(path='/login')=>{
    await send('Page.navigate',{url:'http://127.0.0.1:19118'+path});
    for(let n=0;n<100;n++){if(await evaluate('document.readyState === "complete" && location.pathname === '+JSON.stringify(path.split('?')[0])+' && !!document.querySelector("#email")'))break;await new Promise(r=>setTimeout(r,100));}
    await evaluate('document.fonts.ready.then(()=>true)');
};
const type=async(id,value)=>{
    await evaluate('document.getElementById('+JSON.stringify(id)+').focus();document.getElementById('+JSON.stringify(id)+').select()');
    if(value)await send('Input.insertText',{text:value});
    else for(const kind of ['keyDown','keyUp'])await send('Input.dispatchKeyEvent',{type:kind,key:'Backspace',code:'Backspace',windowsVirtualKeyCode:8});
};
const blur=()=>evaluate('document.activeElement.blur()');
const error=async(id)=>evaluate('(()=>{const e=document.getElementById('+JSON.stringify(id)+'),p=document.getElementById('+JSON.stringify(id+'-error')+');return {invalid:e.getAttribute("aria-invalid"),description:e.getAttribute("aria-describedby"),text:p&&!p.hidden?p.textContent.trim():""}})()');
await send('Emulation.setDeviceMetricsOverride',{width:1440,height:800,deviceScaleFactor:1,mobile:false});
await nav();await evaluate("document.querySelector('#email').focus()");await blur();
assert((await error('email')).invalid==='false','Untouched email must stay valid');
await type('email','abc');await type('email','');await blur();
assert((await error('email')).invalid==='true'&&(await error('email')).text==='Informe seu endereço de e-mail.','Modified then cleared email must show required error');
console.log('PASS: initial interaction scenarios');ws.close();
