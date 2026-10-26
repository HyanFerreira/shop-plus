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
const reports = [];
for (const width of [320, 375, 768, 1024, 1280, 1440]) {
    await send('Emulation.setDeviceMetricsOverride', { width, height: 900, deviceScaleFactor: 1, mobile: false });
    await send('Page.navigate', { url: 'http://127.0.0.1:19118/login' });
    // Wait for the real route and fonts, with a bounded readiness check.
    let ready = false;
    for (let attempt = 0; attempt < 100; attempt++) {
        if (await evaluate("document.readyState === 'complete' && !!document.querySelector('.auth-page--login')")) { ready = true; break; }
        await new Promise(resolve => setTimeout(resolve, 100));
    }
    if (!ready) throw Error('Login did not load');
    await evaluate('document.fonts.ready.then(() => true)');
    const report = await evaluate(`(() => {
        const style = selector => { const s = getComputedStyle(document.querySelector(selector)); return { font: s.fontFamily, size: s.fontSize, height: s.minHeight, background: s.backgroundColor, radius: s.borderRadius }; };
        const rect = selector => { const r = document.querySelector(selector).getBoundingClientRect(); return { x: r.x, width: r.width, height: r.height }; };
        return { width: innerWidth, overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth, panelVisible: getComputedStyle(document.querySelector('.auth-benefits')).display !== 'none', card: rect('.auth-card'), panel: rect('.auth-benefits'), content: rect('.auth-content'), input: style('#email'), button: style('.auth-submit'), title: style('h1') };
    })()`);
    if (report.overflow || report.panelVisible !== (width >= 1024)) throw Error('Responsive layout ' + width);
    if (width >= 1024 && Math.abs(report.panel.width - report.content.width) > 1) throw Error('Unequal columns');
    if (report.input.size !== '16px' || report.input.height !== '44px' || report.button.background !== 'rgb(37, 99, 235)' || !report.input.font.includes('Figtree')) throw Error('Design system mismatch');
    reports.push(report);
    const shot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true });
    fs.writeFileSync(out + '/login-' + width + '.png', Buffer.from(shot.data, 'base64'));
}
await send('Emulation.setDeviceMetricsOverride', { width: 640, height: 450, deviceScaleFactor: 2, mobile: false });
const reflow = await evaluate('({ overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth, width: innerWidth })');
if (reflow.overflow) throw Error('200% equivalent reflow overflow');
const zoomShot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true });
fs.writeFileSync(out + '/login-reflow200.png', Buffer.from(zoomShot.data, 'base64'));
fs.writeFileSync(out + '/login-layout.json', JSON.stringify({ result: 'PASS', reports, reflow }, null, 2));
console.log('PASS: six real /login viewports; equal desktop panels; no overflow; existing control and font tokens');
ws.close();
