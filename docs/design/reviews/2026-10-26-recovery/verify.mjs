// Run against the guarded temporary preview (:19118) and Chrome CDP (:19398).
// Output is isolated under .artifacts/visual-reviews/; see docs/visual-reviews.md for arguments.
import { visualReviewOutput } from '../../../../scripts/visual-review-output.mjs';
import fs from 'node:fs';
import assert from 'node:assert/strict';
const mode = 'recovery';
const out = visualReviewOutput('2026-10-26-recovery', mode, undefined);
fs.mkdirSync(out, { recursive: true });
const target = await (await fetch('http://127.0.0.1:19398/json/new?about:blank', { method: 'PUT' })).json();
const ws = new WebSocket(target.webSocketDebuggerUrl);
await new Promise(r => { ws.onopen = r; });
let id = 0; const pending = new Map();
ws.onmessage = ({ data }) => { const m = JSON.parse(data), p = pending.get(m.id); if (p) { pending.delete(m.id); m.error ? p.reject(m.error) : p.resolve(m.result); } };
const send = (method, params = {}) => new Promise((resolve, reject) => { const callId=++id; const timer=setTimeout(()=>{pending.delete(callId);reject(Error("CDP timeout: "+method));},15000); pending.set(callId, {resolve:v=>{clearTimeout(timer);resolve(v)},reject:e=>{clearTimeout(timer);reject(e)}}); ws.send(JSON.stringify({id,method,params})); });
const evaluate = async expression => { const r = await send('Runtime.evaluate', {expression, returnByValue:true, awaitPromise:true}); if(r.exceptionDetails) throw Error(JSON.stringify(r.exceptionDetails)); return r.result.value; };
const nav = async path => {
    await send('Page.navigate', {url:'http://127.0.0.1:19118'+path});
    for(let i=0;i<100;i++) { if(await evaluate(`document.readyState === 'complete' && location.pathname === ${JSON.stringify(path.split('?')[0])} && !!document.querySelector('#email')`)) break; await new Promise(r=>setTimeout(r,100)); }
    await evaluate('document.fonts.ready.then(()=>true)');
    await evaluate('document.activeElement?.blur(); new Promise(r=>setTimeout(r,250))');
};
const shot = async name => { const r = await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true}); fs.writeFileSync(out+'/'+name+'.png',Buffer.from(r.data,'base64')); };
const type = async (name,value) => { await evaluate(`document.getElementById(${JSON.stringify(name)}).focus();document.getElementById(${JSON.stringify(name)}).select()`); if(value) await send('Input.insertText',{text:value}); else {await send('Input.dispatchKeyEvent',{type:'keyDown',key:'Backspace',windowsVirtualKeyCode:8});await send('Input.dispatchKeyEvent',{type:'keyUp',key:'Backspace',windowsVirtualKeyCode:8});} };
const error = name => evaluate(`(()=>{const e=document.getElementById(${JSON.stringify(name)}),p=document.getElementById(${JSON.stringify(name+'-error')});return {invalid:e.getAttribute('aria-invalid'),text:p&&!p.hidden?p.textContent.trim():''}})()`);

const reports=[];
try {
 await send('Page.enable');await send('Network.enable');await send('Network.setCacheDisabled',{cacheDisabled:true});await send('Emulation.setFocusEmulationEnabled',{enabled:true});
 for(const [width,height,dpr] of [[320,640,1],[375,812,1],[768,900,1],[1024,768,1],[1280,720,1],[1440,900,1],[1440,600,1],[720,450,2]]){
  await send('Emulation.setDeviceMetricsOverride',{width,height,deviceScaleFactor:dpr,mobile:false});
  await nav('/forgot-password');await evaluate('window.scrollTo(0,0)');
  const r=await evaluate(`(()=>{const rect=s=>{const e=document.querySelector(s),c=getComputedStyle(e),r=e.getBoundingClientRect();return {x:r.x,y:r.y,right:r.right,bottom:r.bottom,width:r.width,height:r.height,font:c.fontSize,weight:c.fontWeight,line:c.lineHeight,color:c.color}};const im=document.querySelector('.auth-recovery-illustration');return {width:innerWidth,clientWidth:document.documentElement.clientWidth,height:innerHeight,dpr:devicePixelRatio,scrollHeight:document.documentElement.scrollHeight,overflow:document.documentElement.scrollWidth>document.documentElement.clientWidth,card:rect('.auth-card'),header:rect('.auth-recovery-header'),logo:rect('.auth-recovery-header .ds-shop-logo'),image:rect('.auth-recovery-illustration'),heading:rect('h1'),highlight:rect('h1 span'),description:rect('.auth-heading p'),label:rect('.ds-label'),input:rect('#email'),button:rect('.auth-submit'),footer:rect('.auth-footer'),link:rect('.auth-footer a'),backIcon:rect('.auth-recovery-back-icon'),imageLoaded:im.naturalWidth===1536,imageUrl:im.src,objectFit:getComputedStyle(im).objectFit,curveHeight:parseFloat(getComputedStyle(document.querySelector('header'),'::after').height),fontRequests:performance.getEntriesByType('resource').filter(e=>e.name.endsWith('.woff2')).map(e=>e.name)}})()`);
  assert(!r.overflow);assert.equal(r.input.height,40);assert.equal(r.button.height,40);assert.equal(r.input.width,r.button.width);
  for(const key of ['description','label','input','button','footer','link'])assert.equal(r[key].font,'16px',key+' font '+width);
  assert.equal(r.heading.font,'32px');assert.equal(r.link.height,24);assert.equal(r.backIcon.height,20);assert.equal(r.highlight.color,'rgb(37, 99, 235)');
  assert(r.imageLoaded);assert(r.imageUrl.endsWith('/images/forgout-password.png'));assert.equal(r.objectFit,'contain');assert(Math.abs(r.image.width/r.image.height-1.5)<.001);
  assert(r.logo.bottom<r.header.bottom-r.curveHeight+1,'curve overlaps logo');assert(r.image.bottom<r.heading.y);assert(r.card.width<=464);
  assert(Math.abs(r.card.x+r.card.width/2-r.clientWidth/2)<1);if(r.scrollHeight<=height)assert(Math.abs(r.card.y+r.card.height/2-height/2)<1);
  assert.equal(r.fontRequests.length,3);assert(r.fontRequests.every(x=>x.includes('/fonts/figtree/')));
  reports.push(r);await shot(dpr===2?'recovery-zoom-200':'recovery-'+width+'x'+height);
 }
 fs.writeFileSync(out+'/layout.json',JSON.stringify(reports,null,2));
 await send('Emulation.setDeviceMetricsOverride',{width:1440,height:900,deviceScaleFactor:1,mobile:false});
 await nav('/forgot-password');await evaluate("document.getElementById('email').focus();document.getElementById('email').blur()");assert.equal((await error('email')).invalid,'false','untouched');
 await type('email','temporary');await type('email','');await evaluate('document.activeElement.blur()');assert.equal((await error('email')).invalid,'true','cleared required');
 await type('email','invalid');await evaluate('document.activeElement.blur()');assert.match((await error('email')).text,/válido/);
 await type('email','synthetic@example.test');assert.equal((await error('email')).invalid,'false','corrected');
 await nav('/forgot-password');await evaluate("document.querySelector('.auth-submit').click()");assert.equal((await error('email')).invalid,'true','submit untouched');
 await send('Input.dispatchKeyEvent',{type:'keyDown',key:'Tab',code:'Tab',windowsVirtualKeyCode:9});
 await send('Input.dispatchKeyEvent',{type:'keyUp',key:'Tab',code:'Tab',windowsVirtualKeyCode:9});
 await evaluate("document.getElementById('email').focus();new Promise(r=>setTimeout(r,200))");
 const focus=await evaluate("(()=>{const e=document.getElementById('email'),c=getComputedStyle(e),m=getComputedStyle(document.getElementById('email-error'));return {border:c.borderTopColor,width:c.borderTopWidth,outline:c.outlineColor,messageFont:m.fontSize,described:e.getAttribute('aria-describedby')}})()");
 assert.equal(focus.border,'rgb(220, 38, 38)');assert.equal(focus.width,'1px');assert.equal(focus.outline,'rgb(220, 38, 38)');assert.equal(focus.messageFont,'14px');assert.equal(focus.described,'email-error');
 await shot('recovery-client-error-1440');
 for(const kind of ['field','error','status']){await nav('/__recovery-state/'+kind);await shot('recovery-server-'+kind+'-1440');if(kind==='field'){assert.equal((await error('email')).invalid,'true');await type('email','corrected@example.test');assert.equal((await error('email')).invalid,'false');}}
 await nav('/forgot-password');await type('email','synthetic@example.test');
 // Cancel the native navigation after the shared Alpine submit handler. Inspect its real
 // pending state without sending email; endpoint behavior is covered by isolated PHP tests.
 await evaluate('document.addEventListener("submit",e=>e.preventDefault(),{once:true});document.querySelector("form").requestSubmit();true');
 await new Promise(r=>setTimeout(r,150));assert.equal(await evaluate("document.querySelector('.auth-submit').disabled"),true);assert.equal(await evaluate("document.querySelector('form').getAttribute('aria-busy')"),'true');await shot('recovery-pending-1440');
 await nav('/forgot-password');assert.equal(await evaluate("document.querySelector('.auth-submit').disabled"),false);
 await send('Emulation.setDeviceMetricsOverride',{width:375,height:812,deviceScaleFactor:1,mobile:false});await nav('/forgot-password');await evaluate("document.querySelector('.auth-submit').click()");assert.equal((await error('email')).invalid,'true');assert.equal(await evaluate('document.documentElement.scrollWidth>document.documentElement.clientWidth'),false);await shot('recovery-client-error-375');
 await send('Emulation.setScriptExecutionDisabled',{value:true});await send('Page.navigate',{url:'http://127.0.0.1:19118/forgot-password'});await new Promise(r=>setTimeout(r,500));assert.equal(await evaluate('document.querySelector("form").noValidate'),false);assert.equal(await evaluate('document.querySelector("form").checkValidity()'),false);await send('Emulation.setScriptExecutionDisabled',{value:false});
 fs.writeFileSync(out+'/layout.json',JSON.stringify(reports,null,2));console.log('PASS: six widths, short viewport, 200% equivalent reflow, single card/curve/PNG, shared typography and 40px controls, all email error transitions, server messages, loading state, native fallback.');console.log(JSON.stringify(reports.map(r=>({viewport:r.width+'x'+r.height,dpr:r.dpr,cardHeight:r.card.height,image:r.image.width+'x'+r.image.height,scrollHeight:r.scrollHeight})),null,2));
}finally{ws.close();await fetch('http://127.0.0.1:19398/json/close/'+target.id);}
