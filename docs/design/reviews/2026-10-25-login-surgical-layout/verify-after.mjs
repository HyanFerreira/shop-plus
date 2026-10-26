// Requires the isolated preview from Etapa 8 at :19118 and Chrome CDP at :19398.
// Run from repository root: node <this-file> <evidence-directory>
import { visualReviewOutput } from '../../../../scripts/visual-review-output.mjs';
import fs from 'node:fs';
const out = visualReviewOutput('2026-10-25-login-surgical-layout', '', process.argv[2]);
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
for(const [width,height] of [[1440,800],[1366,681],[1280,720],[1024,768],[768,900],[375,812],[320,640],[1440,600]]){
    await send('Emulation.setDeviceMetricsOverride',{width,height,deviceScaleFactor:1,mobile:false});
    await send('Page.navigate',{url:'http://127.0.0.1:19118/login'});
    for(let n=0;n<100;n++){if(await evaluate('document.readyState === "complete" && !!document.querySelector("#email")'))break;await new Promise(r=>setTimeout(r,100));}
    await evaluate('document.fonts.ready.then(()=>true)');await evaluate('document.activeElement?.blur();new Promise(r=>setTimeout(r,200))');
    const r=await evaluate(`(()=>{const rect=s=>{const e=document.querySelector(s),r=e.getBoundingClientRect(),c=getComputedStyle(e);return {x:r.x,right:r.right,y:r.y,bottom:r.bottom,width:r.width,height:r.height,padding:c.padding,margin:c.margin,font:c.fontSize,line:c.lineHeight}};return {width:innerWidth,height:innerHeight,scrollHeight:document.documentElement.scrollHeight,overflow:document.documentElement.scrollWidth>document.documentElement.clientWidth,card:rect('.auth-card'),panel:rect('.auth-benefits'),group:rect('.auth-benefits > div'),form:rect('.auth-content'),brand:rect('.auth-login-brand'),title:rect('.auth-benefits__welcome'),intro:rect('.auth-benefits__description'),list:rect('.auth-benefits__list'),art:rect('.auth-login-art'),image:rect('.auth-login-art img'),input:rect('#email'),formRadius:getComputedStyle(document.querySelector('.auth-content')).borderTopLeftRadius}})()`);
    if(r.overflow||r.input.height!==40)throw Error('Layout '+width);
    if(width>=1024&&Math.abs(r.panel.right-r.form.x-parseFloat(r.formRadius))>1)throw Error('Background does not cover rounded corners '+JSON.stringify(r));
    const extra=await evaluate(`(()=>{
        const title=document.querySelector('.auth-benefits__welcome'),range=document.createRange();range.selectNodeContents(title.firstChild);
        const first=[...range.getClientRects()].map(r=>({y:r.y,width:r.width}));range.selectNodeContents(title.childNodes[2]);
        const second=[...range.getClientRects()].map(r=>({y:r.y,width:r.width}));
        const rect=s=>{const r=document.querySelector(s).getBoundingClientRect();return {x:r.x,right:r.right,y:r.y,bottom:r.bottom,width:r.width,height:r.height}};
        const form=document.querySelector('.auth-content'),card=document.querySelector('.auth-card'),css=getComputedStyle(card);
        return {clientWidth:document.documentElement.clientWidth,grid:css.gridTemplateColumns.split(' ').map(parseFloat),titleText:[title.firstChild.textContent.trim(),(title.childNodes[2].textContent+title.querySelector('span').textContent).trim()],first,second,titleColor:getComputedStyle(title).color,highlight:getComputedStyle(title.querySelector('span')).color,formHighlight:getComputedStyle(document.querySelector('h1 span')).color,checkbox:rect('#remember_me'),link:rect('.auth-options .auth-link'),list:rect('.auth-benefits__list'),visible:!!title.getClientRects().length,benefits:[...document.querySelectorAll('.auth-benefits__list li')].map(e=>({title:e.querySelector('strong').textContent,description:e.querySelector('.auth-benefits__detail').textContent,font:getComputedStyle(e.querySelector('strong')).fontSize,detailFont:getComputedStyle(e.querySelector('.auth-benefits__detail')).fontSize})),cardHeightRule:css.height,imageLoaded:document.querySelector('.auth-login-art img').naturalWidth===1774};
    })()`);
    if(Math.abs(r.card.x+r.card.width/2-extra.clientWidth/2)>1||extra.checkbox.width!==16||extra.checkbox.height!==16||extra.link.height!==24)throw Error('Center or controls '+JSON.stringify(r));
    if(r.scrollHeight<=height&&Math.abs(r.card.y+r.card.height/2-height/2)>1)throw Error('Vertical centering '+JSON.stringify(r));
    if(width>=1024){
        if(Math.abs(extra.grid[0]/(extra.grid[0]+extra.grid[1])-.55)>.001||r.input.width>416.5||r.input.width<300||r.title.font!=='32px'||r.title.height!==80||extra.first.length!==1||extra.second.length!==1||Math.abs(extra.second[0].y-extra.first[0].y-40)>1||extra.highlight!==extra.formHighlight||extra.highlight===extra.titleColor||JSON.stringify(extra.titleText)!==JSON.stringify(['Sua próxima compra','começa aqui.']))throw Error('Columns/form/title '+JSON.stringify({r,extra}));
        if(!extra.imageLoaded||r.image.y<extra.list.bottom+3.5||r.image.bottom>r.panel.bottom+.5||r.image.right>r.form.x+.5||Math.abs(r.image.width/r.image.height-2)>.01||extra.benefits.length!==3||extra.benefits.some(b=>b.font!=='16px'||b.detailFont!=='14px'))throw Error('Illustration or benefits '+JSON.stringify({r,extra}));
        if(height>=681&&r.scrollHeight>height)throw Error('Unnecessary desktop scrolling '+JSON.stringify(r));
    }
    r.extra=extra;
    reports.push(r);
    const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});fs.writeFileSync(out+'/login-'+width+'x'+height+'.png',Buffer.from(shot.data,'base64'));
}
fs.writeFileSync(out+'/layout.json',JSON.stringify({result:'PASS',reports},null,2));console.log(JSON.stringify(reports.map(r=>({viewport:r.width+'x'+r.height,card:r.card.height,inputWidth:r.input.width,scrollHeight:r.scrollHeight,centerY:r.card.y+r.card.height/2})),null,2));ws.close();
