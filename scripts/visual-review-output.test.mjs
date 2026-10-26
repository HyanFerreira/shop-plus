import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../', import.meta.url));
const base = path.join(root, '.artifacts/visual-reviews');
const { visualReviewOutput } = await import('./visual-review-output.mjs');

test('default runs are isolated and ignored even from a different working directory', () => {
    const previous = process.cwd();
    process.chdir('/tmp');
    try {
        const first = visualReviewOutput('storage-test', 'after');
        const second = visualReviewOutput('storage-test', 'after');
        assert.ok(first.startsWith(base + path.sep));
        assert.notEqual(first, second);
        fs.writeFileSync(path.join(first, 'frame.jpg'), 'first');
        fs.writeFileSync(path.join(second, 'frame.jpg'), 'second');
        assert.equal(fs.readFileSync(path.join(first, 'frame.jpg'), 'utf8'), 'first');
    } finally { process.chdir(previous); }
});

test('explicit shared run supports baseline consumers but refuses tracked destinations and traversal', () => {
    const shared = path.join(base, 'storage-test-shared');
    assert.equal(visualReviewOutput('storage-test', '', shared), shared);
    assert.equal(visualReviewOutput('storage-test', '', shared), shared);
    for (const destination of ['docs/design/reviews/new', base + '/../escape', root]) {
        assert.throws(() => visualReviewOutput('storage-test', '', destination), /artifacts/);
    }
});

test('symlink output cannot escape the artifact directory', () => {
    const link = path.join(base, 'storage-test-link');
    fs.symlinkSync(root, link, 'dir');
    try {
        assert.throws(() => visualReviewOutput('storage-test', '', path.join(link, 'nested')), /artifacts/);
    } finally { fs.unlinkSync(link); }
});

test('all historical browser executors allocate local output before contacting CDP', async () => {
    const reviews = path.join(root, 'docs/design/reviews');
    const scripts = fs.readdirSync(reviews, { recursive: true }).filter(name => name.endsWith('.mjs'));
    assert.equal(scripts.length, 21);
    const before = new Set(fs.readdirSync(base));
    const originalFetch = globalThis.fetch;
    const originalArgv = process.argv;
    let contacts = 0;
    process.argv = [process.execPath, 'storage-probe'];
    globalThis.fetch = async url => {
        assert.equal(url, 'http://127.0.0.1:19398/json/new?about:blank');
        contacts++;
        throw new Error('storage probe stops before browser access');
    };
    try {
        for (const script of scripts) {
            await assert.rejects(import(new URL('../docs/design/reviews/' + script, import.meta.url)), /storage probe stops/);
        }
        assert.equal(contacts, scripts.length);
        const created = fs.readdirSync(base).filter(name => !before.has(name));
        assert.equal(created.length, scripts.length);
    } finally {
        globalThis.fetch = originalFetch;
        process.argv = originalArgv;
    }
});
