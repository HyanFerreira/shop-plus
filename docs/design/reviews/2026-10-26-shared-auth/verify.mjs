// Run against the guarded temporary preview (:19118) and Chrome CDP (:19398).
// Output is isolated under .artifacts/visual-reviews/; see docs/visual-reviews.md for arguments.
import { visualReviewOutput } from '../../../../scripts/visual-review-output.mjs';
import fs from 'node:fs';
import assert from 'node:assert/strict';
const mode = process.argv[2] || 'after';
const out = visualReviewOutput('2026-10-26-shared-auth', mode, process.argv[3]);
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
const reports = [];
try {
    await send('Page.enable'); await send('Network.enable'); await send('Network.setCacheDisabled',{cacheDisabled:true}); await send('Emulation.setFocusEmulationEnabled',{enabled:true});
    if(mode !== 'client') for(const [width,height] of [[320,640],[375,812],[768,900],[1024,768],[1280,720],[1440,800],[1440,600]]) {
        await send('Emulation.setDeviceMetricsOverride',{width,height,deviceScaleFactor:1,mobile:false});
        for(const page of mode==='before'?['login']:['login','register']) {
            await nav('/'+page);
            const r=await evaluate(`(()=>{const rect=s=>{const e=document.querySelector(s),r=e.getBoundingClientRect(),c=getComputedStyle(e);return {x:r.x,y:r.y,right:r.right,bottom:r.bottom,width:r.width,height:r.height,font:c.fontSize,display:c.display}};return {width:innerWidth,clientWidth:document.documentElement.clientWidth,height:innerHeight,overflow:document.documentElement.scrollWidth>document.documentElement.clientWidth,scrollHeight:document.documentElement.scrollHeight,card:rect('.auth-card'),panel:rect('.auth-benefits'),image:rect('.auth-login-art img'),list:rect('.auth-benefits__list'),heading:rect('h1'),label:rect('.ds-label'),description:rect('.auth-heading p'),footer:rect('.auth-footer'),footerLink:rect('.auth-footer .auth-link'),submit:rect('.auth-submit'),inputs:[...document.querySelectorAll('.ds-input')].map(e=>({name:e.name,height:e.getBoundingClientRect().height,width:e.getBoundingClientRect().width})),aside:document.querySelector('aside').outerHTML,imageLoaded:document.querySelector('.auth-login-art img').naturalWidth===1774}})()`);
            assert(!r.overflow,`${page} overflow ${width}`); assert(r.inputs.every(i=>i.height===40),`${page} input height ${width}`);assert.equal(r.submit.height,40);
            assert(Math.abs(r.card.x+r.card.width/2-r.clientWidth/2)<1,'horizontal center');
            if(r.scrollHeight<=height)assert(Math.abs(r.card.y+r.card.height/2-height/2)<1,'vertical center');
            if(width>=1024) {assert(r.imageLoaded);assert(r.image.bottom<=r.panel.bottom+1);assert(r.image.y>=r.list.bottom+3);assert(r.inputs.every(i=>i.width<=416.5));}
            if(page==='register') {assert.equal(r.heading.font,'32px');assert.equal(r.label.font,'16px');assert.equal(r.description.font,'16px');assert.equal(r.footer.font,'16px');assert.equal(r.footerLink.font,'16px');assert.equal(r.submit.font,'16px');assert.equal(r.aside,reports.at(-1).aside);}
            reports.push({page,...r}); await shot(page+'-'+width+'x'+height);
        }
    }
    fs.writeFileSync(out+'/layout.json',JSON.stringify(reports,null,2));
    if(mode!=='before') {
        await send('Emulation.setDeviceMetricsOverride',{width:1440,height:800,deviceScaleFactor:1,mobile:false});
        await nav('/register');
        await evaluate("document.getElementById('name').focus();document.getElementById('name').blur()");
        assert.equal((await error('name')).invalid,'false','untouched field');
        await evaluate("document.querySelector('.auth-submit').click()");
        for(const name of ['name','email','password','password_confirmation'])assert.equal((await error(name)).invalid,'true',name+' required');
        assert.equal(await evaluate("document.querySelector('.auth-submit').disabled"),false,'invalid form loading');
        await shot('register-required');
        await type('name','Synthetic User');assert.equal((await error('name')).invalid,'false');
        await type('email','invalid');assert.equal((await error('email')).invalid,'true');
        await type('email','synthetic@example.test');assert.equal((await error('email')).invalid,'false');
        await type('password','short');assert.equal((await error('password')).invalid,'true','min password');
        await type('password','Synthetic123!');assert.equal((await error('password')).invalid,'false');
        await type('password_confirmation','different');assert.equal((await error('password_confirmation')).invalid,'true');
        await type('password_confirmation','Synthetic123!');assert.equal((await error('password_confirmation')).invalid,'false');
        await type('password','Changed123!');assert.equal((await error('password_confirmation')).invalid,'true','confirmation dependency');
        await type('password','Synthetic123!');assert.equal((await error('password_confirmation')).invalid,'false');
        for(const name of ['name','email','password','password_confirmation']) {
            await evaluate(`(()=>{const e=document.getElementById('${name}');e.value='x'.repeat(Number(e.maxLength)+1);e.dispatchEvent(new Event('input',{bubbles:true}))})()`);
            assert.equal((await error(name)).invalid,'true',name+' max');
        }
        await nav('/register');
        for(const name of ['password','password_confirmation']) {
            await evaluate(`document.querySelector('[aria-controls="${name}"]').click()`);
            assert.equal(await evaluate(`document.getElementById('${name}').type`),'text');
            assert.equal(await evaluate(`document.querySelector('[aria-controls="${name}"]').getAttribute('aria-pressed')`),'true');
            await evaluate(`document.querySelector('[aria-controls="${name}"]').click()`);
            assert.equal(await evaluate(`document.getElementById('${name}').type`),'password');
        }
        await nav('/register');
        await type('name','Nome Ágil'); await type('email','boundary@example.test');
        await type('password','12345678'); await evaluate("document.getElementById('password').blur()");
        assert.equal((await error('password')).invalid,'false','minimum boundary');
        await type('password_confirmation','12345678'); await evaluate("document.getElementById('password_confirmation').blur()");
        assert.equal((await error('password_confirmation')).invalid,'false');
        await nav('/__auth-errors');
        assert.equal((await error('email')).invalid,'true','server duplicate');
        await evaluate("document.getElementById('email').focus();document.getElementById('email').blur()");
        assert.match((await error('email')).text,/already been taken/,'retain unchanged server message');
        await evaluate("document.getElementById('email').focus()");
        const invalidFocus = await evaluate("(()=>{const e=document.getElementById('email'),c=getComputedStyle(e);return {border:c.borderTopColor,width:c.borderTopWidth,outline:c.outlineColor,description:e.getAttribute('aria-describedby')}})()");
        assert.equal(invalidFocus.border,'rgb(220, 38, 38)');assert.equal(invalidFocus.width,'1px');assert.equal(invalidFocus.description,'email-error');
        await shot('register-server-error');
        await type('email','corrected@example.test');assert.equal((await error('email')).invalid,'false','clear corrected server error');
        await nav('/__auth-errors?kind=confirmation');
        assert.equal((await error('password_confirmation')).invalid,'true','Fortify confirmed mapping');
        assert.equal((await error('password')).invalid,'false');
        await type('password','Synthetic123!');await type('password_confirmation','Synthetic123!');
        assert.equal((await error('password_confirmation')).invalid,'false','correct server confirmed');
        await send('Emulation.setDeviceMetricsOverride',{width:320,height:640,deviceScaleFactor:1,mobile:false});
        await nav('/register');await evaluate("document.querySelector('.auth-submit').click()");
        assert.equal(await evaluate('document.documentElement.scrollWidth>document.documentElement.clientWidth'),false,'errors reflow');
        await shot('register-required-320');
        await send('Emulation.setScriptExecutionDisabled',{value:true});
        await send('Page.navigate',{url:'http://127.0.0.1:19118/register'});
        await new Promise(r=>setTimeout(r,500));
        assert.equal(await evaluate('document.querySelector("form").noValidate'),false,'native fallback');
        assert.equal(await evaluate('document.querySelector("form").checkValidity()'),false,'native required validation');
        await send('Emulation.setScriptExecutionDisabled',{value:false});
        await nav('/login');await evaluate("document.querySelector('.auth-submit').click()");
        for(const name of ['email','password'])assert.equal((await error(name)).invalid,'true');
        await type('email','synthetic@example.test');await type('password','Synthetic123!');
        for(const name of ['email','password'])assert.equal((await error(name)).invalid,'false');
        console.log('PASS client validation: untouched, required, correction, email format, policy, confirmation dependency, max limits, toggles, login smoke');
    }
    console.log('PASS '+mode+': '+reports.length+' captures');
} finally { ws.close(); await fetch('http://127.0.0.1:19398/json/close/'+target.id); }
