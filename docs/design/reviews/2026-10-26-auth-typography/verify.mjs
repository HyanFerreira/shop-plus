// Run against the guarded temporary preview (:19118) and Chrome CDP (:19398).
// Output is isolated under .artifacts/visual-reviews/; see docs/visual-reviews.md for arguments.
import { visualReviewOutput } from '../../../../scripts/visual-review-output.mjs';
import fs from 'node:fs';
import assert from 'node:assert/strict';
const mode = process.argv[2] || 'before';
const out = visualReviewOutput('2026-10-26-auth-typography', mode, undefined);
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
const reports=[];
try {
    await send('Page.enable');await send('Network.enable');await send('Network.setCacheDisabled',{cacheDisabled:true});await send('Emulation.setFocusEmulationEnabled',{enabled:true});
    for(const width of [320,375,768,1024,1280,1440]) {
        await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:false});
        for(const page of ['login','register']) {
            await nav('/'+page);
            const result=await evaluate(`(()=>{
                const style=e=>{const c=getComputedStyle(e),r=e.getBoundingClientRect();return Object.fromEntries(['fontFamily','fontSize','fontWeight','lineHeight','letterSpacing','paddingTop','paddingBottom','paddingLeft','paddingRight','gap','alignItems','justifyContent','backgroundColor','borderRadius','color'].map(p=>[p,c[p]]).concat([['class',e.className],['width',r.width],['height',r.height],['x',r.x],['y',r.y]]))};
                const selectors={button:'.auth-submit',buttonText:'.auth-submit > span',footer:'.auth-footer',footerText:'.auth-footer > span',footerLink:'.auth-footer .auth-link',heading:'h1',description:'.auth-heading p',label:'.ds-label',input:'.ds-input',auxiliary:'.auth-options .auth-link',requirements:'.auth-password-requirements',fieldError:'.auth-login-error',card:'.auth-card',content:'.auth-content',panel:'.auth-benefits',image:'.auth-login-art img'};
                return {width:innerWidth,page:location.pathname,overflow:document.documentElement.scrollWidth>document.documentElement.clientWidth,styles:Object.fromEntries(Object.entries(selectors).map(([key,selector])=>[key,document.querySelector(selector)?style(document.querySelector(selector)):null]))};
            })()`);
            reports.push(result);
            if(width===375||width===1440)await shot(page+'-'+width);
            if(mode==='after') {
                const s=result.styles;
                for(const name of ['button','footer','footerText','footerLink','input','label','description'])assert.equal(s[name].fontSize,'16px',page+' '+name+' '+width);
                assert.equal(s.button.height,40);assert.equal(s.input.height,40);assert(!result.overflow);
                assert(parseFloat(s.heading.fontSize)<=32);assert.equal(s.fieldError.fontSize,'14px');
                if(s.auxiliary)assert.equal(s.auxiliary.fontSize,'16px');
                if(s.requirements)assert.equal(s.requirements.fontSize,'14px');
                if(page==='register') {
                    const login=reports.at(-2).styles;
                    for(const key of ['button','footer','footerText','footerLink'])for(const prop of ['fontFamily','fontSize','fontWeight','lineHeight','letterSpacing'])assert.equal(s[key][prop],login[key][prop],key+' '+prop+' '+width);
                    for(const prop of ['paddingTop','paddingBottom','paddingLeft','paddingRight','alignItems','justifyContent','gap'])assert.equal(s.button[prop],login.button[prop],'button '+prop);
                }
            }
        }
    }
    fs.writeFileSync(out+'/computed.json',JSON.stringify(reports,null,2));
    console.log(JSON.stringify(reports.filter(r=>r.width===1440||r.width===320).map(r=>({width:r.width,page:r.page,button:[r.styles.button.fontSize,r.styles.button.fontWeight,r.styles.button.lineHeight,r.styles.button.paddingTop],footer:[r.styles.footer.fontSize,r.styles.footer.lineHeight,r.styles.footer.height],input:[r.styles.input.fontSize,r.styles.input.lineHeight],card:r.styles.card.height})),null,2));
    console.log('PASS '+mode);
} finally {ws.close();await fetch('http://127.0.0.1:19398/json/close/'+target.id);}
