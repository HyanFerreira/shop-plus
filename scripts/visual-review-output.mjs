import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../', import.meta.url));
const base = path.join(root, '.artifacts', 'visual-reviews');

// Historical executors may share an explicit run for baseline/consumer checks.
// Defaults always allocate a fresh run, preserving earlier captures.
export function visualReviewOutput(review, phase = '', requested) {
    const output = requested ? path.resolve(root, requested) : base;
    if (output !== base && !output.startsWith(base + path.sep)) {
        throw new Error('Visual output must stay inside .artifacts/visual-reviews/');
    }
    let current = root;
    for (const segment of path.relative(root, output).split(path.sep)) {
        current = path.join(current, segment);
        if (fs.existsSync(current) && fs.lstatSync(current).isSymbolicLink()) {
            throw new Error('Symlinks are not allowed in visual .artifacts output');
        }
    }
    fs.mkdirSync(output, { recursive: true });
    if (requested) return output;
    const label = [review, phase].filter(Boolean).join('-').replace(/[^a-zA-Z0-9_-]/g, '_');
    return fs.mkdtempSync(path.join(base, label + '-'));
}
