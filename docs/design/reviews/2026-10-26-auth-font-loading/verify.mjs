// Run against the guarded temporary preview (:19118) and Chrome CDP (:19398).
// Output is isolated under .artifacts/visual-reviews/; see docs/visual-reviews.md for arguments.
import { visualReviewOutput } from '../../../../scripts/visual-review-output.mjs';
import fs from 'node:fs';
import assert from 'node:assert/strict';
const mode = process.argv[2] || 'before';
const delivery = process.argv[3] || 'build';
const out = visualReviewOutput('2026-10-26-auth-font-loading', mode + '-' + delivery, undefined);
fs.mkdirSync(out, { recursive: true });
const target = await (await fetch('http://127.0.0.1:19398/json/new?about:blank', { method: 'PUT' })).json();
const ws = new WebSocket(target.webSocketDebuggerUrl);
await new Promise(r => { ws.onopen = r; });
let id = 0; const pending = new Map();
ws.onmessage = ({ data }) => { const m = JSON.parse(data), p = pending.get(m.id); if (p) { pending.delete(m.id); m.error ? p.reject(m.error) : p.resolve(m.result); } };
const send = (method, params = {}) => new Promise((resolve, reject) => { pending.set(++id, {resolve,reject}); ws.send(JSON.stringify({id,method,params})); });
const evaluate = async expression => { const r = await send('Runtime.evaluate', {expression, returnByValue:true, awaitPromise:true}); if(r.exceptionDetails) throw Error(JSON.stringify(r.exceptionDetails)); return r.result.value; };

const selectors={logo:'.auth-benefits__logo',panelTitle:'.auth-benefits__welcome',title:'h1',benefit:'.auth-benefits__list strong',label:'.ds-label',input:'.ds-input',link:'.auth-footer a',button:'.auth-submit',footer:'.auth-footer',card:'.auth-card',image:'.auth-login-art img'};
let run=null,frames=[],requests=[],firstPlatforms=null;
ws.addEventListener('message',async({data})=>{
 const m=JSON.parse(data),p=m.params;
 if(m.method==='Page.screencastFrame') {await send('Page.screencastFrameAck',{sessionId:p.sessionId});if(run&&frames.length<25){const file=run+'-'+String(frames.length).padStart(2,'0')+'.jpg';fs.writeFileSync(out+'/'+file,Buffer.from(p.data,'base64'));frames.push({file,...p.metadata});}}
 if(run&&m.method==='Network.requestWillBeSent')requests.push({id:p.requestId,url:p.request.url,type:p.type,priority:p.request.initialPriority,start:p.timestamp,wallTime:p.wallTime,initiator:p.initiator.type});
 if(run&&m.method==='Network.responseReceived'){const r=requests.find(x=>x.id===p.requestId);if(r)Object.assign(r,{mime:p.response.mimeType,status:p.response.status,diskCache:p.response.fromDiskCache});}
 if(run&&m.method==='Network.loadingFinished'){const r=requests.find(x=>x.id===p.requestId);if(r)Object.assign(r,{end:p.timestamp,bytes:p.encodedDataLength});}
});
const sleep=ms=>new Promise(r=>setTimeout(r,ms));
const platforms=async(attempt=0)=>{
 try {
 const {root}=await send('DOM.getDocument');const result={};
 for(const [key,selector] of Object.entries(selectors))if(!['card','image'].includes(key)){
  const {nodeId}=await send('DOM.querySelector',{nodeId:root.nodeId,selector:key==='button'?'.auth-submit > span:first-child':selector});
  if(nodeId)result[key]=(await send('CSS.getPlatformFontsForNode',{nodeId})).fonts;
 }
 return result;
 }catch(error){if(attempt<3){await sleep(30);return platforms(attempt+1);}throw error;}
};
const reports=[];
try{
 await send('Page.enable');if(delivery==='dev')await send('Page.setBypassCSP',{enabled:true});await send('Network.enable');await send('DOM.enable');await send('CSS.enable');await send('Emulation.setFocusEmulationEnabled',{enabled:true});
 await send('Page.addScriptToEvaluateOnNewDocument',{source:`
 window.__fontProbe={states:[],shifts:[],paints:[],classes:[]};
 const selectors=${JSON.stringify(selectors)};
 new PerformanceObserver(list=>{for(const e of list.getEntries())if(!e.hadRecentInput)window.__fontProbe.shifts.push({time:e.startTime,value:e.value,nodes:e.sources.map(s=>s.node?.className||s.node?.nodeName)});}).observe({type:'layout-shift',buffered:true});
 new PerformanceObserver(list=>{for(const e of list.getEntries())window.__fontProbe.paints.push({name:e.name,time:e.startTime});}).observe({type:'paint',buffered:true});
 new MutationObserver(list=>{for(const e of list)if(e.type==='attributes'&&e.attributeName==='class')window.__fontProbe.classes.push({time:performance.now(),node:e.target.tagName,value:e.target.className});}).observe(document,{subtree:true,attributes:true,attributeFilter:['class']});
 let previous='',count=0;
 const sample=()=>{const card=document.querySelector('.auth-card');if(card){const values={fontsStatus:document.fonts.status,faces:[...document.fonts].map(f=>({family:f.family,weight:f.weight,status:f.status})),styles:{},zoom:visualViewport.scale,dpr:devicePixelRatio};for(const [key,selector] of Object.entries(selectors)){const e=document.querySelector(selector);if(e){const c=getComputedStyle(e),r=e.getBoundingClientRect();values.styles[key]={fontFamily:c.fontFamily,fontSize:c.fontSize,fontWeight:c.fontWeight,lineHeight:c.lineHeight,letterSpacing:c.letterSpacing,x:r.x,y:r.y,width:r.width,height:r.height,display:c.display};}}const serialized=JSON.stringify(values);if(serialized!==previous){window.__fontProbe.states.push({time:performance.now(),...values});previous=serialized;}}if(++count<1200)requestAnimationFrame(sample);};requestAnimationFrame(sample);
 `});
 const scenarios=[...[320,375,768,1024,1280,1440].map(width=>({width,kind:'cold'})),{width:1440,kind:'reload'},{width:1440,kind:'hard'},{width:1440,kind:'slow'},{width:375,kind:'slow'},{width:1440,kind:'navigation'}];
 for(const page of ['login','register'])for(const {width,kind} of scenarios){
  const slow=kind==='slow';await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:false});
  await send('Network.emulateNetworkConditions',{offline:false,latency:slow?100:0,downloadThroughput:slow?200000:-1,uploadThroughput:slow?200000:-1});
  await send('Network.setCacheDisabled',{cacheDisabled:kind!=='reload'&&kind!=='navigation'});
  if(kind==='cold'||slow||kind==='hard')await send('Network.clearBrowserCache');
  run=null;
  if(kind==='navigation'){
   const other=page==='login'?'register':'login';await send('Page.navigate',{url:'http://127.0.0.1:19118/'+other});
   for(let i=0;i<300;i++){if(await evaluate(`location.pathname==='/${other}'&&document.readyState==='complete'&&!!document.querySelector('.auth-footer a')`))break;await sleep(50);}
  }
  run=page+'-'+width+'-'+kind;frames=[];requests=[];firstPlatforms=null;
  const capture=(width===1440&&(kind==='hard'||slow))||(width===375&&slow);
  if(capture)await send('Page.startScreencast',{format:'jpeg',quality:80,maxWidth:1440,maxHeight:1000,everyNthFrame:1});
  const oldOrigin=await evaluate('performance.timeOrigin');
  if(kind==='reload'||kind==='hard')await send('Page.reload',{ignoreCache:kind==='hard'});
  else if(kind==='navigation')await evaluate('document.querySelector(".auth-footer a").click()');
  else await send('Page.navigate',{url:'http://127.0.0.1:19118/'+page});
  let done=false;
  for(let i=0;i<500;i++){
   const status=await evaluate(`({newDoc:performance.timeOrigin!==${oldOrigin},page:location.pathname,ready:document.readyState,painted:!!window.__fontProbe?.paints.length,hasState:!!window.__fontProbe?.states.length,hasTitle:!!document.querySelector('h1')})`);
   if(status.newDoc&&status.page==='/'+page&&status.hasState&&status.hasTitle&&!firstPlatforms)firstPlatforms=await platforms();
   if(status.newDoc&&status.page==='/'+page&&status.ready==='complete'&&status.hasTitle){done=true;break;}
   await sleep(25);
  }
  if(!done)throw Error('Timed out '+run);
  await evaluate('document.fonts.ready.then(()=>true)');await sleep(200);
  const lastPlatforms=await platforms();
  const data=await evaluate(`({timeOrigin:performance.timeOrigin,probe:window.__fontProbe,resources:performance.getEntriesByType('resource').map(e=>({name:e.name,type:e.initiatorType,start:e.startTime,end:e.responseEnd,bytes:e.encodedBodySize})),styleSheets:[...document.styleSheets].map(s=>s.href||'inline'),fontsLoaded:[...document.fonts].map(f=>({family:f.family,weight:f.weight,status:f.status})),overflow:document.documentElement.scrollWidth>document.documentElement.clientWidth})`);
  if(capture)await send('Page.stopScreencast');
  await evaluate('document.activeElement?.blur()');await sleep(150);
  if(kind==='cold'){const screenshot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});fs.writeFileSync(out+'/'+run+'-final.png',Buffer.from(screenshot.data,'base64'));}
  const r={run,page,width,kind,delivery,data,firstPlatforms,lastPlatforms,frames,requests};reports.push(r);fs.writeFileSync(out+'/'+run+'.json',JSON.stringify(r,null,2));
  const fcp=data.probe.paints.find(e=>e.name==='first-contentful-paint')?.time;
  const stableStates=data.probe.states.filter(s=>s.time>=fcp);
  const changes=Object.entries(selectors).map(([key])=>({key,sizes:[...new Set(stableStates.map(s=>JSON.stringify(s.styles[key])))] .length}));
  const final=data.probe.states.at(-1);
  assert(!data.overflow);assert.equal(final.styles.input.height,40);assert.equal(final.styles.button.height,40);
  if(mode==='after'){
   assert(requests.every(r=>!r.url.includes('fonts.bunny.net')&&!r.url.includes('fonts.googleapis.com')&&!r.url.includes('fonts.gstatic.com')),'external font dependency');
   assert(Object.values(lastPlatforms).flat().every(f=>f.isCustomFont&&f.familyName.startsWith('Figtree')),'final font differs');
   assert(Object.values(firstPlatforms||{}).flat().every(f=>f.isCustomFont&&f.familyName.startsWith('Figtree')),'fallback visible at first paint');
   assert(changes.every(c=>c.sizes<=1),'geometry or styles changed after first paint '+run+' '+JSON.stringify(changes));
  }
  console.log(JSON.stringify({run,fcp,firstFont:[...new Set(Object.values(firstPlatforms||{}).flat().map(f=>f.familyName))],lastFont:[...new Set(Object.values(lastPlatforms).flat().map(f=>f.familyName))],stylesChanges:changes.filter(c=>c.sizes>1),cls:data.probe.shifts.reduce((a,s)=>a+s.value,0),fontResources:requests.filter(r=>r.type==='Font').map(r=>({url:r.url,priority:r.priority,start:r.start,end:r.end})),frames:frames.length}));
  run=null;
 }
 fs.writeFileSync(out+'/summary.json',JSON.stringify(reports.map(r=>({run:r.run,firstPlatforms:r.firstPlatforms,lastPlatforms:r.lastPlatforms,states:r.data.probe.states,shifts:r.data.probe.shifts,paints:r.data.probe.paints,requests:r.requests})),null,2));
}finally{ws.close();await fetch('http://127.0.0.1:19398/json/close/'+target.id);}
