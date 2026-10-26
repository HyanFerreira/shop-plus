// Presentation only: a pointer-focused text field should not display the Tab outline.
const root = document.documentElement;
root.dataset.focusModality = 'pointer';

document.addEventListener('keydown', (event) => {
    if (event.key === 'Tab') {
        root.dataset.focusModality = 'keyboard';
    }
}, true);

document.addEventListener('pointerdown', () => {
    root.dataset.focusModality = 'pointer';
}, true);
