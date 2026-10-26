import assert from 'node:assert/strict';
import { test } from 'node:test';

globalThis.document = { addEventListener() {} };
const { favoriteButton } = await import('./favorites.js');

function setup(favorited = false) {
    const events = [], redirects = [], requests = [];
    globalThis.window = {
        location: { assign: url => redirects.push(url) },
        dispatchEvent: event => events.push(event),
    };
    const button = favoriteButton('/favoritos/42', favorited, 'Produto fictício');
    button.$el = { querySelector: () => ({ value: 'synthetic-csrf' }) };
    globalThis.fetch = async (url, options) => {
        requests.push({ url, ...options });
        return { ok: true, status: 200, json: async () => ({ favorited: !favorited }) };
    };
    return { button, requests, events, redirects };
}

test('save/remove use explicit methods and CSRF; synchronize the confirmed server state', async () => {
    for (const initial of [false, true]) {
        const { button, requests, events } = setup(initial);
        await button.submit();
        assert.equal(requests[0].method, initial ? 'DELETE' : 'POST');
        assert.equal(requests[0].headers['X-CSRF-TOKEN'], 'synthetic-csrf');
        assert.equal(requests[0].credentials, 'same-origin');
        assert.equal(button.favorited, !initial);
        assert.equal(events[0].type, 'favorite-changed');
        assert.deepEqual(events[0].detail, { url: '/favoritos/42', favorited: !initial });
        assert.equal(button.busy, false);
    }
});

test('rapid repeated clicks send one request while pending', async () => {
    const { button } = setup();
    let resolve, count = 0;
    globalThis.fetch = () => { count++; return new Promise(r => { resolve = r; }); };
    const pending = button.submit();
    await button.submit();
    assert.equal(count, 1);
    resolve({ ok: true, json: async () => ({ favorited: true }) });
    await pending;
    assert.equal(button.favorited, true);
    assert.equal(button.busy, false);
});

test('failed requests preserve state and announce the error; expired authentication opens login', async () => {
    for (const status of [401, 419, 404, 500]) {
        const { button, events, redirects } = setup(true);
        globalThis.fetch = async () => ({ ok: false, status });
        await button.submit();
        assert.equal(button.favorited, true);
        assert.equal(button.busy, false);
        assert.equal(events.length, 0);
        if (status === 401) assert.deepEqual(redirects, ['/login']);
        else assert.match(button.message, /Tente novamente/);
    }
    const { button } = setup();
    globalThis.fetch = async () => { throw new Error('offline'); };
    await button.submit();
    assert.equal(button.favorited, false);
    assert.match(button.message, /Tente novamente/);
});
