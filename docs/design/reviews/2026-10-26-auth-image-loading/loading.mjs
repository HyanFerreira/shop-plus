// Run against the guarded temporary preview (:19118) and Chrome CDP (:19398).
// Output is isolated under .artifacts/visual-reviews/; see docs/visual-reviews.md for arguments.
import { visualReviewOutput } from '../../../../scripts/visual-review-output.mjs';
import fs from 'node:fs';
import assert from 'node:assert/strict';
const mode = process.argv[2] || 'before';
const out = visualReviewOutput('2026-10-26-auth-image-loading', mode, undefined);
fs.mkdirSync(out, { recursive: true });
const target = await (await fetch('http://127.0.0.1:19398/json/new?about:blank', { method: 'PUT' })).json();
const ws = new WebSocket(target.webSocketDebuggerUrl);
await new Promise(r => { ws.onopen = r; });
let id = 0; const pending = new Map();
ws.onmessage = ({ data }) => { const m = JSON.parse(data), p = pending.get(m.id); if (p) { pending.delete(m.id); m.error ? p.reject(m.error) : p.resolve(m.result); } };
const send = (method, params = {}) => new Promise((resolve, reject) => { pending.set(++id, {resolve,reject}); ws.send(JSON.stringify({id,method,params})); });
const evaluate = async expression => { const r = await send('Runtime.evaluate', {expression, returnByValue:true, awaitPromise:true}); if(r.exceptionDetails) throw Error(JSON.stringify(r.exceptionDetails)); return r.result.value; };

let scenario=null, activeFrames=[],network=[],trace=[],tracingDone=null;
ws.addEventListener('message',async({data})=>{
    const m=JSON.parse(data);
    if(m.method==='Page.screencastFrame') {
        await send('Page.screencastFrameAck',{sessionId:m.params.sessionId});
        if(scenario&&activeFrames.length<30){const file=`${scenario}-${String(activeFrames.length).padStart(2,'0')}.jpg`;fs.writeFileSync(out+'/'+file,Buffer.from(m.params.data,'base64'));activeFrames.push({file,...m.params.metadata});}
    }
    if(scenario&&m.method?.startsWith('Network.')) {
        const p=m.params;
        if(m.method==='Network.requestWillBeSent')network.push({type:'request',id:p.requestId,url:p.request.url,timestamp:p.timestamp,priority:p.request.initialPriority,initiator:p.initiator.type});
        if(m.method==='Network.responseReceived')network.push({type:'response',id:p.requestId,timestamp:p.timestamp,status:p.response.status,cache:p.response.fromDiskCache,mime:p.response.mimeType});
        if(m.method==='Network.loadingFinished')network.push({type:'finished',id:p.requestId,timestamp:p.timestamp,bytes:p.encodedDataLength});
        if(m.method==='Network.resourceChangedPriority')network.push({type:'priority',id:p.requestId,timestamp:p.timestamp,priority:p.newPriority});
    }
    if(m.method==='Tracing.tracingComplete'&&tracingDone){tracingDone();tracingDone=null;}
    if(m.method==='Tracing.dataCollected')trace.push(...m.params.value.filter(e=>/Decode|PaintImage|Draw Lazy|LayoutShift/.test(e.name)));
});
const sleep=ms=>new Promise(r=>setTimeout(r,ms));
const reports=[];
try {
 await send('Page.enable');await send('Network.enable');
 await send('Emulation.setDeviceMetricsOverride',{width:1440,height:900,deviceScaleFactor:1,mobile:false});
 await send('Page.addScriptToEvaluateOnNewDocument',{source:`
    window.__imageProbe={events:[],shifts:[]};
    new PerformanceObserver(list=>{for(const e of list.getEntries())if(!e.hadRecentInput)window.__imageProbe.shifts.push({time:e.startTime,value:e.value,sources:e.sources.map(s=>({node:s.node?.className||s.node?.nodeName,previous:s.previousRect,current:s.currentRect}))});}).observe({type:'layout-shift',buffered:true});
    new PerformanceObserver(list=>{for(const e of list.getEntries())window.__imageProbe.events.push({type:'paint',name:e.name,time:e.startTime});}).observe({type:'paint',buffered:true});
    new PerformanceObserver(list=>{for(const e of list.getEntries())window.__imageProbe.events.push({type:'lcp',time:e.renderTime||e.loadTime,url:e.url});}).observe({type:'largest-contentful-paint',buffered:true});
    document.addEventListener('DOMContentLoaded',()=>{const im=document.querySelector('.auth-login-art img');if(im){let samples=0;let previous='';const sample=()=>{const c=getComputedStyle(im),p=getComputedStyle(im.parentElement),r=im.getBoundingClientRect(),card=document.querySelector('.auth-card').getBoundingClientRect();const value={loaded:im.complete&&im.naturalWidth>0,x:r.x,y:r.y,width:r.width,height:r.height,cardHeight:card.height,display:c.display,parentDisplay:p.display,opacity:c.opacity,visibility:c.visibility,transform:c.transform,animation:c.animationName,transition:c.transitionProperty};const serialized=JSON.stringify(value);if(serialized!==previous){window.__imageProbe.events.push({type:'image-state',time:performance.now(),...value});previous=serialized;}if(++samples<600)requestAnimationFrame(sample);};requestAnimationFrame(sample);im.addEventListener('load',()=>window.__imageProbe.events.push({type:'image-load',time:performance.now()}));}});
 `});
 for(const route of (process.argv[3] ? [process.argv[3]] : ['login','register']))for(const kind of ['cold','reload','hard-reload','navigation','slow']) {
    await send('Network.emulateNetworkConditions',{offline:false,latency:kind==='slow'?100:0,downloadThroughput:kind==='slow'?200000:-1,uploadThroughput:kind==='slow'?200000:-1});
    if(kind==='cold'||kind==='slow'){await send('Network.clearBrowserCache');await send('Network.setCacheDisabled',{cacheDisabled:true});}else await send('Network.setCacheDisabled',{cacheDisabled:false});
    activeFrames=[];network=[];trace=[];scenario=route+'-'+kind;
    await send('Tracing.start',{categories:'devtools.timeline,disabled-by-default-devtools.timeline',transferMode:'ReportEvents'});
    await send('Page.startScreencast',{format:'jpeg',quality:80,maxWidth:1440,maxHeight:900,everyNthFrame:1});
    if(kind==='reload'||kind==='hard-reload')await send('Page.reload',{ignoreCache:kind==='hard-reload'});
    else if(kind==='navigation') {
        const opposite=route==='login'?'register':'login';
        await send('Page.navigate',{url:'http://127.0.0.1:19118/'+opposite});
        for(let i=0;i<200;i++){if(await evaluate(`document.readyState==='complete'&&location.pathname==='/${opposite}'&&!!document.querySelector('.auth-footer a')`))break;await sleep(50);}
        activeFrames=[];network=[];trace=[];
        await evaluate('document.querySelector(".auth-footer a").click()');
    } else await send('Page.navigate',{url:'http://127.0.0.1:19118/'+route});
    let ready=false;
    for(let i=0;i<300;i++){if(await evaluate(`document.readyState==='complete'&&location.pathname==='/${route}'&&!!document.querySelector('.auth-login-art img')&&document.querySelector('.auth-login-art img').complete`)){ready=true;break;}await sleep(50);}
    if(!ready)throw Error('Timed out '+scenario);
    await evaluate('document.fonts.ready.then(()=>true)');await sleep(300);
    const metrics=await evaluate(`(()=>{const i=document.querySelector('.auth-login-art img'),c=getComputedStyle(i);return {probe:window.__imageProbe,resource:performance.getEntriesByName(i.src).map(e=>e.toJSON()),image:{url:i.src,loaded:i.naturalWidth===1774,loading:i.loading,decoding:i.decoding,priority:i.fetchPriority,width:i.width,height:i.height,animation:c.animationName,opacity:c.opacity,visibility:c.visibility},preloads:[...document.querySelectorAll('link[rel=preload][as=image]')].map(e=>({href:e.href,media:e.media})),cardHeight:document.querySelector('.auth-card').getBoundingClientRect().height,overflow:document.documentElement.scrollWidth>document.documentElement.clientWidth}})()`);
    await send('Page.stopScreencast');const completed=new Promise(resolve=>{tracingDone=resolve;});await send('Tracing.end');await completed;
    const imageRequests=network.filter(e=>e.type==='request'&&e.url.endsWith('/images/login.png'));
    assert(metrics.image.loaded);assert.equal(imageRequests.length,1,scenario+' duplicate request');assert(!metrics.overflow);
    const states=metrics.probe.events.filter(e=>e.type==='image-state');
    assert(states.every(e=>e.opacity==='1'&&e.visibility==='visible'&&e.animation==='none'));
    assert(new Set(states.map(e=>e.width+'x'+e.height)).size<=1,scenario+' image area unstable');
    assert(!states.some((e,i)=>e.loaded&&states.slice(i+1).some(next=>!next.loaded)),scenario+' image disappeared');
    const r={scenario,metrics,frames:activeFrames,network,decodeTrace:trace};reports.push(r);
    fs.writeFileSync(out+'/'+scenario+'.json',JSON.stringify(r,null,2));
    const resource=metrics.resource[0];console.log(JSON.stringify({scenario,requestStart:resource?.startTime,responseEnd:resource?.responseEnd,paint:metrics.probe.events.filter(e=>e.type==='paint'),imageStates:states.map(e=>({time:e.time,loaded:e.loaded,cardHeight:e.cardHeight})),layoutShifts:metrics.probe.shifts,initialPriority:imageRequests[0]?.priority,decodeEvents:trace.filter(e=>/Decode/.test(e.name)).map(e=>({name:e.name,dur:e.dur})),frames:activeFrames.length}));
    scenario=null;
 }
 fs.writeFileSync(out+'/summary.json',JSON.stringify(reports.map(r=>({scenario:r.scenario,resource:r.metrics.resource,events:r.metrics.probe.events,shifts:r.metrics.probe.shifts,image:r.metrics.image,preloads:r.metrics.preloads,cardHeight:r.metrics.cardHeight})),null,2));
} finally {ws.close();await fetch('http://127.0.0.1:19398/json/close/'+target.id);}
