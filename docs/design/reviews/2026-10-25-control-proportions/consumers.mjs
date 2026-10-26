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

const reports=[];
const paths=['/','/catalogo','/catalogo/produto-controles','/carrinho','/checkout','/dashboard','/meus-dados','/user/profile','/admin/catalogo','/admin/abastecimento','/admin/pedidos','/admin/auditoria','/admin/usuarios'];
for(const path of paths) for(const width of [375,1440]) {
    await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:false});
    await send('Page.navigate',{url:'http://127.0.0.1:19119'+path+'?scenario='+(path.startsWith('/admin')?'admin':'full')});
    let ready=false;
    for(let n=0;n<150;n++) { if(await evaluate('document.readyState === "complete" && location.pathname === '+JSON.stringify(path)+' && !!document.querySelector(".storefront-header")')){ready=true;break;} await new Promise(r=>setTimeout(r,100)); }
    if(!ready) throw Error('Route did not load: '+path+' '+await evaluate('document.body.innerText.slice(0,500)'));
    await evaluate('document.fonts.ready.then(()=>true)');
    const r=await evaluate(`(()=>{
        const visible=e=>!!e.getClientRects().length;
        const textRows=e=>{const tops=new Set();const walker=document.createTreeWalker(e,NodeFilter.SHOW_TEXT);for(let n;n=walker.nextNode();)if(n.textContent.trim()){const range=document.createRange();range.selectNodeContents(n);for(const r of range.getClientRects())tops.add(Math.round(r.top));}return tops.size;};
        const normal=e=>{const c=getComputedStyle(e),r=e.getBoundingClientRect();return {id:e.id||e.textContent.trim().slice(0,60),tag:e.tagName,height:r.height,rows:textRows(e),font:c.fontSize};};
        const fields=[...document.querySelectorAll('input.ds-input,input.ds-field,select.ds-input,select.ds-field')].filter(e=>visible(e)&&!['hidden','file','checkbox','radio'].includes(e.type)&&!e.multiple).map(normal);
        const actions=[...document.querySelectorAll('.ds-button,main button.bg-indigo-600,main button.bg-red-700')].filter(e=>visible(e)&&!e.matches('.storefront-search__submit,.cart-quantity .ds-button,.product-quantity .ds-button')).map(normal);
        const headings=[...document.querySelectorAll('h1,h2,h3')].filter(visible).map(e=>({text:e.textContent.trim().slice(0,60),font:parseFloat(getComputedStyle(e).fontSize)}));
        const search=document.querySelector('.storefront-search__submit').getBoundingClientRect();
        const nav=[...document.querySelectorAll('.storefront-nav > a,.storefront-action')].filter(visible).map(e=>e.getBoundingClientRect().height);
        const quantity=[...document.querySelectorAll('.cart-quantity,.product-quantity')].map(e=>{const i=e.querySelector('input').getBoundingClientRect(),b=e.querySelector('button').getBoundingClientRect();return {input:i.height,button:b.height,centerDifference:Math.abs((i.top+i.height/2)-(b.top+b.height/2))};});
        return {overflow:document.documentElement.scrollWidth>document.documentElement.clientWidth,overflowBy:document.documentElement.scrollWidth-document.documentElement.clientWidth,fields,actions,headings,search:{width:search.width,height:search.height},nav,quantity};
    })()`);
    reports.push({path,width,...r});
    fs.writeFileSync(out+'/consumers.json',JSON.stringify({reports},null,2));
    const baseline=JSON.parse(fs.readFileSync(out+'/admin-orders-baseline.json','utf8'));
    const unchangedOverflow=path==='/admin/pedidos'&&width===375&&r.overflowBy<=baseline.baseline.scroll-baseline.baseline.client;
    if(unchangedOverflow&&r.overflow)reports.at(-1).note='Existing 2px admin-orders overflow reproduced with baseline CSS; unchanged by control geometry';
    if((r.overflow&&!unchangedOverflow)||r.fields.some(c=>c.height!==40)||r.actions.some(c=>c.height!==40&&!(c.height>40&&c.rows>1))||r.headings.some(h=>h.font>32)||r.search.width!==44||r.search.height!==44||r.nav.some(h=>h!==44)||r.quantity.some(q=>q.input!==40||q.centerDifference>1||![44,48].includes(q.button))) throw Error('Consumer contract '+path+' '+width+' '+JSON.stringify(r));
    if(width===1440||path==='/meus-dados'){
        const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});
        fs.writeFileSync(out+'/consumer-'+(path==='/'?'home':path.replaceAll('/','-').slice(1))+'-'+width+'.png',Buffer.from(shot.data,'base64'));
    }
}
fs.writeFileSync(out+'/consumers.json',JSON.stringify({result:'PASS (no new regressions)',conditions:'Guarded SQLite :memory:, fictional fixtures, readonly real routes; normal single-line action/field geometry and preserved special controls.',baselineLimitations:['Existing 2px overflow at /admin/pedidos375, unchanged against baseline CSS; see admin-orders-baseline.json'],reports},null,2));
console.log('PASS: '+reports.length+' real storefront/account/admin combinations');ws.close();
