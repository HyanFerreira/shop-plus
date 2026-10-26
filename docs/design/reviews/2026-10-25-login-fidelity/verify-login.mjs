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
const reports = [];
for (const width of [320, 375, 768, 1024, 1280, 1440, 1672]) {
    await send('Emulation.setDeviceMetricsOverride', { width, height: width === 1672 ? 843 : 900, deviceScaleFactor: 1, mobile: false });
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
    if (width >= 1024 && Math.abs(report.panel.width / report.content.width - 48 / 52) > .01) throw Error('Panel ratio differs from 48/52');
    if (report.input.size !== (width >= 1280 ? '18px' : '16px') || report.input.height !== '56px' || report.button.background !== 'rgb(37, 99, 235)' || !report.input.font.includes('Figtree')) throw Error('Design system mismatch');
    const geometry = await evaluate(`(() => {
        const box = document.querySelector('.auth-content').getBoundingClientRect();
        const controls = [...document.querySelectorAll('.auth-content input:not([type=hidden]), .auth-content button, .auth-content a')].filter(e => e.getClientRects().length && e.type !== 'checkbox');
        return { controlsInside: controls.every(e => { const r=e.getBoundingClientRect(); return r.left >= box.left && r.right <= box.right && r.height >= 44; }), imageLoaded: !${width >= 1024} || (document.querySelector('.auth-login-art img').complete && document.querySelector('.auth-login-art img').naturalWidth === 1774), titleCount: document.querySelectorAll('h1').length, emailPadding: getComputedStyle(document.querySelector('#email')).paddingLeft, decorative: getComputedStyle(document.querySelector('.auth-page'), '::before').content };
    })()`);
    if (!geometry.controlsInside || !geometry.imageLoaded || geometry.titleCount !== 1 || geometry.emailPadding !== (width >= 1280 ? '64px' : '48px') || geometry.decorative === 'none') throw Error('Control/image/decorative geometry ' + width);
    report.geometry = geometry;
    reports.push(report);
    await evaluate('document.activeElement?.blur()');
    const shot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true });
    fs.writeFileSync(out + '/login-' + width + '.png', Buffer.from(shot.data, 'base64'));
}
await send('Emulation.setDeviceMetricsOverride', { width: 640, height: 450, deviceScaleFactor: 2, mobile: false });
const reflow = await evaluate('({ overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth, width: innerWidth })');
if (reflow.overflow) throw Error('200% equivalent reflow overflow');
const zoomShot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true });
fs.writeFileSync(out + '/login-reflow200.png', Buffer.from(zoomShot.data, 'base64'));
const checks = [];
const navigate = async (path, selector = '.auth-page--login') => {
    await send('Page.navigate', { url: 'http://127.0.0.1:19118' + path });
    for (let attempt = 0; attempt < 100; attempt++) {
        if (await evaluate('document.readyState === "complete" && location.pathname === ' + JSON.stringify(path.split('?')[0]) + ' && !!document.querySelector(' + JSON.stringify(selector) + ')')) {
            await evaluate('document.fonts.ready.then(() => true)'); return;
        }
        await new Promise(resolve => setTimeout(resolve, 100));
    }
    throw Error('Page not ready: ' + path);
};
await send('Emulation.setDeviceMetricsOverride', { width: 375, height: 900, deviceScaleFactor: 1, mobile: false });
await navigate('/login');
await evaluate("document.getElementById('password').value='synthetic-password'; document.querySelector('.auth-password__toggle').focus()");
for (const type of ['keyDown', 'keyUp']) await send('Input.dispatchKeyEvent', { type, key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13, text: type === 'keyDown' ? '\r' : undefined });
if (!await evaluate("document.getElementById('password').type === 'text' && document.querySelector('.auth-password__toggle').getAttribute('aria-pressed') === 'true' && document.querySelector('.auth-password__toggle').getAttribute('aria-label') === 'Ocultar senha: Senha' && getComputedStyle(document.activeElement).outlineStyle === 'solid'")) throw Error('Keyboard/accessible toggle');
checks.push('Password icon toggle: keyboard Enter, input type, aria-pressed, accessible label and visible focus pass');
if (!await evaluate("document.querySelector('[name=_token]').value.length > 0 && document.querySelector('form').method === 'post' && new URL(document.querySelector('form').action).pathname === '/login'")) throw Error('Form contract');
await evaluate("document.querySelector('#email').value='invalid'; document.querySelector('#remember_me').click()");
if (!await evaluate("!document.querySelector('#email').validity.valid && document.querySelector('#remember_me').checked")) throw Error('Native email/remember');
await evaluate("document.querySelector('form').addEventListener('submit', e => e.preventDefault()); document.querySelector('form').dispatchEvent(new Event('submit', {bubbles:true,cancelable:true}))");
if (!await evaluate("document.querySelector('.auth-submit').disabled && document.querySelector('form').getAttribute('aria-busy') === 'true' && !document.querySelector('#password').disabled && !document.querySelector('[name=_token]').disabled")) throw Error('Loading contract');
checks.push('Existing POST/CSRF, email validation, remember checkbox and loading presentation pass; no submit sent');
for (const width of [375, 1440]) {
    await send('Emulation.setDeviceMetricsOverride', { width, height: 900, deviceScaleFactor: 1, mobile: false });
    await navigate('/_preview/login?state=error', '[aria-invalid=true]');
    if (!await evaluate("document.documentElement.scrollWidth === document.documentElement.clientWidth && [...document.querySelectorAll('[aria-invalid=true]')].every(e => e.getAttribute('aria-describedby').split(' ').every(id => !!document.getElementById(id)))")) throw Error('Error state');
    const shot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true });
    fs.writeFileSync(out + '/login-error-' + width + '.png', Buffer.from(shot.data, 'base64'));
}
checks.push('Synthetic server error states at 375/1440: associated errors and no overflow');
await send('Network.enable');
await send('Network.setBlockedURLs', { urls: ['*livewire.js*', '*/build/assets/*.js'] });
await navigate('/login');
if (!await evaluate("typeof Alpine === 'undefined' && document.querySelector('#password').type === 'password' && !document.querySelector('.auth-submit').disabled && getComputedStyle(document.querySelector('.auth-password__toggle')).display === 'none' && document.querySelector('.auth-submit').textContent.includes('Entrar')")) throw Error('No JavaScript fallback');
await send('Network.setBlockedURLs', { urls: [] });
checks.push('Without JavaScript: native password/submit usable and inactive toggle hidden');
for (const screen of ['register', 'reset-password', 'forgot-password']) {
    await navigate('/_preview/' + screen, '.auth-page');
    if (!await evaluate("!document.querySelector('.auth-page--login') && !document.querySelector('.auth-login-art') && !document.querySelector('.ds-shop-logo__accent') && [...document.querySelectorAll('.auth-password__toggle')].every(e => e.textContent.includes('Mostrar')) && document.documentElement.scrollWidth === document.documentElement.clientWidth")) throw Error('Other auth default ' + screen);
}
checks.push('Register/reset/recovery default presentation stays intact, including textual password toggle and original logo');
fs.writeFileSync(out + '/login-layout.json', JSON.stringify({ result: 'PASS', reports, reflow, checks }, null, 2));
console.log('PASS: seven real /login viewports; 48/52 desktop panels; no overflow; existing control and font tokens');
ws.close();
