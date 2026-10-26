// Requires the isolated preview from Etapa 8 at :19118 and Chrome CDP at :19398.
// Run from repository root: node <this-file> <evidence-directory>
import fs from 'node:fs';
const out = process.argv[2];
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

await send('Emulation.setDeviceMetricsOverride',{width:1440,height:900,deviceScaleFactor:1,mobile:false});
await send('Page.navigate',{url:'http://127.0.0.1:19118/login'});
for(let n=0;n<100;n++){if(await evaluate('document.readyState === "complete" && !!document.querySelector("#email")'))break;await new Promise(r=>setTimeout(r,100));}
await evaluate('document.fonts.ready.then(()=>true)');
await evaluate('document.activeElement.blur(); new Promise(r=>setTimeout(r,200))');
const clip=await evaluate("(()=>{const r=document.querySelector('#email').getBoundingClientRect(),l=document.querySelector('label[for=email]').getBoundingClientRect();return {x:r.x-4,y:l.y-4,width:r.width+8,height:r.bottom-l.y+8,scale:1}})()");
const reports=[];
const capture=async name=>{
    const metrics=await evaluate("(()=>{const e=document.querySelector('#email'),c=getComputedStyle(e),label=document.querySelector('label[for=email]');return {focused:document.activeElement===e,border:c.borderColor,borderWidth:c.borderWidth,shadow:c.boxShadow,outline:c.outlineStyle,outlineWidth:c.outlineWidth,offset:c.outlineOffset,labelWeight:getComputedStyle(label).fontWeight,height:e.getBoundingClientRect().height,radius:c.borderRadius}})()");
    const shot=await send('Page.captureScreenshot',{format:'png',clip,captureBeyondViewport:true});fs.writeFileSync(out+'/'+name+'.png',Buffer.from(shot.data,'base64'));reports.push({name,...metrics});
};
await capture('field-normal');
const point=await evaluate("(()=>{const r=document.querySelector('#email').getBoundingClientRect();return {x:r.x+80,y:r.y+r.height/2}})()");
for(const type of ['mousePressed','mouseReleased'])await send('Input.dispatchMouseEvent',{type,...point,button:'left',clickCount:1});
await evaluate('new Promise(r=>setTimeout(r,200))');await capture('field-pointer-focus');
for(const modifiers of [8,0])for(const type of ['keyDown','keyUp'])await send('Input.dispatchKeyEvent',{type,key:'Tab',code:'Tab',windowsVirtualKeyCode:9,modifiers});
await evaluate('new Promise(r=>setTimeout(r,200))');await capture('field-keyboard-focus');
fs.writeFileSync(out+'/field-states.json',JSON.stringify({reports},null,2));console.log('PASS: captured normal, pointer and Tab field states');ws.close();
