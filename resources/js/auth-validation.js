function initializeAuthValidation() {
    const form = document.querySelector('form[data-auth-validation]');
    if (!form || form.dataset.authValidationReady) return;

    const serverErrors = JSON.parse(form.dataset.authErrors || '{}');
    const announcement = form.querySelector('[data-auth-announcement]');
    const registration = form.dataset.authValidation === 'register';
    const labels = { name: 'Nome', email: 'E-mail', password: 'Senha', password_confirmation: 'Confirmar senha' };
    const fields = [...form.querySelectorAll('[data-auth-field]')].map((input) => ({
        input,
        name: input.dataset.authField,
        lastValue: input.value,
        modified: false,
        checked: Boolean(serverErrors[input.dataset.authField]),
        serverMessage: serverErrors[input.dataset.authField] || '',
        message: form.querySelector(`[data-auth-error="${input.dataset.authField}"]`),
    }));

    function passwordRequirements() {
        const password = form.elements.password.value;
        const confirmation = form.elements.password_confirmation.value;
        const length = Array.from(password).length;
        return {
            length: length >= 8 && length <= 128,
            lowercase: /\p{Ll}/u.test(password),
            uppercase: /\p{Lu}/u.test(password),
            // Match Laravel Password::symbols(): Unicode separators, symbols and punctuation.
            symbols: /[\p{Z}\p{S}\p{P}]/u.test(password),
            confirmation: password !== '' && confirmation !== '' && password === confirmation,
        };
    }

    function updatePasswordRequirements() {
        if (!registration) return;
        const requirements = passwordRequirements();
        for (const item of form.querySelectorAll('[data-password-requirement]')) {
            const met = requirements[item.dataset.passwordRequirement];
            if (item.dataset.met !== String(met)) {
                item.dataset.met = String(met);
                item.querySelector('[data-requirement-status]').textContent = met ? ' Atendido.' : ' Pendente.';
            }
        }
    }

    function localError(field) {
        const value = field.input.value;
        if (value.trim() === '') return {
            name: 'Informe seu nome.',
            email: 'Informe seu endereço de e-mail.',
            password: 'Informe sua senha.',
            password_confirmation: 'Confirme sua senha.',
        }[field.name];
        if (field.name === 'email' && field.input.validity.typeMismatch) return 'Informe um endereço de e-mail válido.';
        const limit = ['name', 'email'].includes(field.name) ? 255 : 128;
        const length = Array.from(value).length;
        if (length > limit) return {
            name: 'Use um nome com até 255 caracteres.',
            email: 'Use um e-mail com até 255 caracteres.',
            password: 'Use uma senha com até 128 caracteres.',
            password_confirmation: 'Use uma senha com até 128 caracteres.',
        }[field.name];
        if (registration && field.name === 'password') {
            const requirements = passwordRequirements();
            if (length < 8) return 'Use uma senha com pelo menos 8 caracteres.';
            if (!requirements.lowercase || !requirements.uppercase) return 'Inclua letras maiúsculas e minúsculas.';
            if (!requirements.symbols) return 'Inclua pelo menos um caractere especial.';
        }
        if (field.name === 'password_confirmation' && value !== form.elements.password.value) return 'As senhas devem ser iguais.';
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
        const text = field.message.querySelector('[data-auth-error-text]');
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
            const changed = synchronize(field);
            if (changed) updatePasswordRequirements();
            if (changed && field.checked) display(field, localError(field));
            if (changed && field.name === 'password') {
                const confirmation = fields.find(candidate => candidate.name === 'password_confirmation');
                if (confirmation?.checked) {
                    confirmation.serverMessage = '';
                    display(confirmation, localError(confirmation));
                }
            }
        };
        field.input.addEventListener('input', update);
        field.input.addEventListener('change', update);
        field.input.addEventListener('blur', () => {
            synchronize(field);
            updatePasswordRequirements();
            if (!field.modified && !field.checked) return;
            field.checked = true;
            display(field, field.serverMessage || localError(field), true);
        });
    }

    form.addEventListener('submit', (event) => {
        updatePasswordRequirements();
        let firstInvalid = null;
        for (const field of fields) {
            synchronize(field);
            field.checked = true;
            field.serverMessage = '';
            const message = localError(field);
            display(field, message);
            if (message && !firstInvalid) firstInvalid = field.input;
        }
        // Preserve required consent validation when Jetstream enables terms.
        const terms = form.querySelector('input[name="terms"][required]');
        if (!firstInvalid && terms && !terms.checkValidity()) {
            event.preventDefault();
            event.stopImmediatePropagation();
            terms.reportValidity();
            return;
        }
        if (firstInvalid) {
            event.preventDefault();
            // Run before the shared Alpine handler so invalid forms never enter the loading state.
            event.stopImmediatePropagation();
            announcement.textContent = '';
            if (document.activeElement !== firstInvalid) firstInvalid.focus();
        }
    }, true);

    updatePasswordRequirements();

    // Keep native required/type validation as a fallback when JavaScript is unavailable.
    form.noValidate = true;
    form.dataset.authValidationReady = 'true';
}

initializeAuthValidation();
document.addEventListener('livewire:navigated', initializeAuthValidation);
