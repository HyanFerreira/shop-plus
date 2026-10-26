export function favoriteButton(url, favorited, name) {
    return {
        url, favorited, busy: false, message: '', hasError: false,
        get label() {
            return `${this.favorited ? 'Remover dos favoritos' : 'Adicionar aos favoritos'}: ${name}`;
        },
        async submit() {
            if (this.busy) return;
            this.busy = true;
            this.message = '';
            this.hasError = false;
            try {
                const response = await fetch(this.url, {
                    method: this.favorited ? 'DELETE' : 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.$el.querySelector('[name="_token"]').value,
                    },
                });
                if (response.status === 401) {
                    window.location.assign('/login');
                    return;
                }
                if (!response.ok) throw new Error('favorite-request-failed');
                const data = await response.json();
                this.favorited = data.favorited;
                this.message = this.favorited ? 'Produto adicionado aos favoritos.' : 'Produto removido dos favoritos.';
                window.dispatchEvent(new CustomEvent('favorite-changed', {
                    detail: { url: this.url, favorited: this.favorited },
                }));
            } catch {
                this.hasError = true;
                this.message = 'Não foi possível atualizar os favoritos. Tente novamente.';
            } finally {
                this.busy = false;
            }
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('favoriteButton', favoriteButton);
});
