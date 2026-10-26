function initializeLoginValidation() {
    const form = document.querySelector('form[data-login-validation]');
    if (!form || form.dataset.loginValidationReady) return;

    const serverErrors = JSON.parse(form.dataset.loginErrors || '{}');
    const announcement = form.querySelector('[data-login-announcement]');
    const labels = { email: 'E-mail', password: 'Senha' };
    const fields = [...form.querySelectorAll('[data-login-field]')].map((input) => ({
        input,
        name: input.dataset.loginField,
        lastValue: input.value,
        modified: false,
        checked: Boolean(serverErrors[input.dataset.loginField]),
        serverMessage: serverErrors[input.dataset.loginField] || '',
        message: form.querySelector(`[data-login-error="${input.dataset.loginField}"]`),
    }));

    function localError(field) {
        const value = field.input.value;
        if (value.trim() === '') return field.name === 'email'
            ? 'Informe seu endereço de e-mail.' : 'Informe sua senha.';
        if (field.name === 'email' && field.input.validity.typeMismatch) return 'Informe um endereço de e-mail válido.';
        const limit = field.name === 'email' ? 255 : 128;
        if (Array.from(value).length > limit) return field.name === 'email'
            ? 'Use um e-mail com até 255 caracteres.' : 'Use uma senha com até 128 caracteres.';
        return '';
    }

    function synchronize(field) {
        if (field.input.value === field.lastValue) return false;
        field.modified = true;
        field.lastValue = field.input.value;
        field.serverMessage = '';
        const credentials = document.querySelector('[data-login-credentials="true"]');
        if (credentials) credentials.hidden = true;
        return true;
    }

    function display(field, message, announce = false) {
        const text = field.message.querySelector('[data-login-error-text]');
        if (text.textContent !== message) text.textContent = message;
        if (field.message.hidden !== (message === '')) field.message.hidden = message === '';
        const invalid = message ? 'true' : 'false';
        if (field.input.getAttribute('aria-invalid') !== invalid) field.input.setAttribute('aria-invalid', invalid);
        if (message && field.input.getAttribute('aria-describedby') !== field.message.id) field.input.setAttribute('aria-describedby', field.message.id);
        else if (!message && field.input.hasAttribute('aria-describedby')) field.input.removeAttribute('aria-describedby');
        // Only blur announces errors; keystrokes update the field without repeated live announcements.
        if (announce && message) {
            const text = `${labels[field.name]}: ${message}`;
            if (announcement.textContent !== text) announcement.textContent = text;
        } else if (!message && announcement.textContent.startsWith(`${labels[field.name]}:`)) {
            announcement.textContent = '';
        }
    }

    for (const field of fields) {
        const update = () => {
            if (synchronize(field) && field.checked) display(field, localError(field));
        };
        field.input.addEventListener('input', update);
        field.input.addEventListener('change', update);
        field.input.addEventListener('blur', () => {
            synchronize(field);
            if (!field.modified && !field.checked) return;
            field.checked = true;
            display(field, field.serverMessage || localError(field), true);
        });
    }

    form.addEventListener('submit', (event) => {
        let firstInvalid = null;
        for (const field of fields) {
            synchronize(field);
            field.checked = true;
            field.serverMessage = '';
            const message = localError(field);
            display(field, message);
            if (message && !firstInvalid) firstInvalid = field.input;
        }
        if (firstInvalid) {
            event.preventDefault();
            // Run before the shared Alpine handler so invalid forms never enter the loading state.
            event.stopImmediatePropagation();
            announcement.textContent = '';
            if (document.activeElement !== firstInvalid) firstInvalid.focus();
        }
    }, true);

    // Keep native required/type validation as a fallback when JavaScript is unavailable.
    form.noValidate = true;
    form.dataset.loginValidationReady = 'true';
}

initializeLoginValidation();
document.addEventListener('livewire:navigated', initializeLoginValidation);
