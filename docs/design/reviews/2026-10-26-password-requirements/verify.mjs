// Run against the guarded temporary preview (:19118) and Chrome CDP (:19398).
// Output is isolated under .artifacts/visual-reviews/; see docs/visual-reviews.md for arguments.
import { visualReviewOutput } from '../../../../scripts/visual-review-output.mjs';
import fs from 'node:fs';
import assert from 'node:assert/strict';
const mode = 'after';
const out = visualReviewOutput('2026-10-26-password-requirements', mode, process.argv[2]);
fs.mkdirSync(out, { recursive: true });
const target = await (await fetch('http://127.0.0.1:19398/json/new?about:blank', { method: 'PUT' })).json();
const ws = new WebSocket(target.webSocketDebuggerUrl);
await new Promise(r => { ws.onopen = r; });
let id = 0; const pending = new Map();
ws.onmessage = ({ data }) => { const m = JSON.parse(data), p = pending.get(m.id); if (p) { pending.delete(m.id); m.error ? p.reject(m.error) : p.resolve(m.result); } };
const send = (method, params = {}) => new Promise((resolve, reject) => { pending.set(++id, {resolve,reject}); ws.send(JSON.stringify({id,method,params})); });
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
const states = () => evaluate("Object.fromEntries([...document.querySelectorAll('[data-password-requirement]')].map(e=>[e.dataset.passwordRequirement,e.dataset.met==='true']))");
const expected = async value => assert.deepEqual(await states(),value);
const neutral = {length:false,lowercase:false,uppercase:false,symbols:false,confirmation:false};
const layout = [];
try {
    await send('Page.enable');await send('Network.enable');await send('Network.setCacheDisabled',{cacheDisabled:true});await send('Emulation.setFocusEmulationEnabled',{enabled:true});
    for(const [width,height] of [[320,640],[375,812],[768,900],[1024,768],[1280,720],[1440,800],[1440,600]]) {
        await send('Emulation.setDeviceMetricsOverride',{width,height,deviceScaleFactor:1,mobile:false});
        await nav('/login');await shot('login-'+width+'x'+height);
        await nav('/register');await expected(neutral);
        const geometry=await evaluate(`(()=>{const rect=s=>{const r=document.querySelector(s).getBoundingClientRect();return {x:r.x,y:r.y,width:r.width,height:r.height,right:r.right,bottom:r.bottom}};return {width:innerWidth,clientWidth:document.documentElement.clientWidth,height:innerHeight,scrollHeight:document.documentElement.scrollHeight,overflow:document.documentElement.scrollWidth>document.documentElement.clientWidth,card:rect('.auth-card'),panel:rect('.auth-benefits'),image:rect('.auth-login-art img'),form:rect('.auth-form'),checklist:rect('.auth-password-requirements'),confirmation:rect('#password_confirmation'),submit:rect('.auth-submit'),inputs:[...document.querySelectorAll('.ds-input')].map(e=>e.getBoundingClientRect().height),columns:getComputedStyle(document.querySelector('.auth-password-requirements ul')).gridTemplateColumns}})()`);
        assert(!geometry.overflow);assert(geometry.inputs.every(x=>x===40));assert.equal(geometry.submit.height,40);
        assert.equal(geometry.submit.y-geometry.checklist.bottom,16,'button spacing');
        assert.equal(geometry.checklist.y-geometry.confirmation.bottom,16,'checklist spacing');
        assert(Math.abs(geometry.card.x+geometry.card.width/2-geometry.clientWidth/2)<1);
        if(geometry.scrollHeight<=height)assert(Math.abs(geometry.card.y+geometry.card.height/2-height/2)<1);
        if(width>=1024){assert(geometry.form.width<=416.5);assert(geometry.image.bottom<=geometry.panel.bottom+1);}
        layout.push(geometry);await shot('register-neutral-'+width+'x'+height);
    }
    for(const width of [1440,375]) {
        await send('Emulation.setDeviceMetricsOverride',{width,height:width===1440?900:812,deviceScaleFactor:1,mobile:false});
        await nav('/register');await evaluate("document.getElementById('password').focus();document.getElementById('password').blur()");
        assert.equal((await error('password')).invalid,'false','untouched');await expected(neutral);
        await type('password','abc');await expected({...neutral,lowercase:true});
        await shot('register-partial-'+width);
        await evaluate("document.getElementById('password').blur()");assert.equal((await error('password')).invalid,'true','short password');
        for(const [password,flags] of [
            ['ABCDEFG!',{...neutral,length:true,uppercase:true,symbols:true}],
            ['abcdefg!',{...neutral,length:true,lowercase:true,symbols:true}],
            ['Abcdefgh',{...neutral,length:true,lowercase:true,uppercase:true}],
            ['1234567!',{...neutral,length:true,symbols:true}],
        ]) {await type('password',password);await expected(flags);assert.equal((await error('password')).invalid,'true','missing criterion');}
        await type('password','Abcdefg!');await expected({length:true,lowercase:true,uppercase:true,symbols:true,confirmation:false});
        assert.equal((await error('password')).invalid,'false');
        await type('password_confirmation','different');await evaluate("document.getElementById('password_confirmation').blur()");assert.equal((await error('password_confirmation')).invalid,'true');
        await type('password_confirmation','Abcdefg!');await expected({length:true,lowercase:true,uppercase:true,symbols:true,confirmation:true});assert.equal((await error('password_confirmation')).invalid,'false');
        await type('name','Synthetic User');await type('email','synthetic@example.test');await evaluate('document.activeElement.blur()');await shot('register-complete-'+width);
        await type('password','Changed!');assert.equal((await states()).confirmation,false);assert.equal((await error('password_confirmation')).invalid,'true');
        await type('password','Abcdefg!');assert.equal((await states()).confirmation,true);
        await type('password_confirmation','');assert.equal((await states()).confirmation,false);
        await type('password','');await expected(neutral);
        await evaluate("document.querySelector('.auth-submit').click()");assert.equal((await error('password')).invalid,'true');assert.equal((await error('password_confirmation')).invalid,'true');assert.equal(await evaluate("document.querySelector('.auth-submit').disabled"),false);
        await shot('register-errors-'+width);
        await type('password','Ábcdefg★');assert.equal((await error('password')).invalid,'false','unicode policy');
        await type('password_confirmation','Ábcdefg★');assert.equal((await states()).confirmation,true);
        await type('password','Abcdefg ');assert.equal((await error('password')).invalid,'false','Laravel symbols includes separators');
        await type('password','Abcdefgh');await type('password_confirmation','Abcdefgh');await evaluate("document.querySelector('.auth-submit').click()");assert.equal((await error('password')).invalid,'true','submit rejects policy despite matching');assert.equal(await evaluate("document.querySelector('.auth-submit').disabled"),false);
        assert.equal(await evaluate('document.documentElement.scrollWidth>document.documentElement.clientWidth'),false,'errors reflow');
    }
    fs.writeFileSync(out+'/layout.json',JSON.stringify(layout,null,2));
    console.log('PASS: empty/partial/complete indicators, all policy criteria, Unicode, empty/mismatched/matching confirmation, edits, submit, errors, desktop/mobile, control heights and natural layout.');
    console.log(JSON.stringify(layout.map(r=>({viewport:r.width+'x'+r.height,cardHeight:r.card.height,formWidth:r.form.width,scrollHeight:r.scrollHeight})),null,2));
} finally {ws.close();await fetch('http://127.0.0.1:19398/json/close/'+target.id);}
