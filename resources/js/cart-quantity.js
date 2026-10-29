export function cartQuantityUpdates() {
    return {
        busy: false,
        message: '',
        draft: null,
        async perform(operation) {
            if (this.busy) return false;
            this.busy = true;
            this.message = '';
            try {
                return await operation();
            } catch {
                this.message = 'Não foi possível atualizar o carrinho. Tente novamente.';
                return false;
            } finally {
                this.busy = false;
            }
        },
        stageQuantity(itemId, value) {
            if (!this.busy) this.draft = { itemId, value };
        },
        async flushDraft() {
            if (!this.draft) return true;
            const { itemId, value } = this.draft;
            this.$wire.set(`quantities.${itemId}`, value, false);
            const saved = await this.$wire.updateItem(itemId);
            if (saved === true) this.draft = null;
            return saved === true;
        },
        saveQuantity(itemId, value) {
            if (this.busy) return;
            this.stageQuantity(itemId, value);
            return this.perform(() => this.flushDraft());
        },
        changeQuantity(itemId, delta, limit) {
            if (this.busy || limit < 1) return;
            const current = Number(this.$wire.quantities[itemId]);
            const next = Math.max(1, Math.min(limit, (Number.isFinite(current) ? current : 1) + delta));
            if (next === current) return;
            return this.perform(async () => {
                if (this.draft && this.draft.itemId !== itemId && !await this.flushDraft()) return false;
                this.draft = { itemId, value: next };
                return this.flushDraft();
            });
        },
        removeItem(itemId) {
            return this.perform(async () => {
                if (this.draft?.itemId === itemId) this.draft = null;
                if (!await this.flushDraft()) return false;
                await this.$wire.removeItem(itemId);
            });
        },
        clearCart() {
            return this.perform(() => {
                this.draft = null;
                return this.$wire.clearCart();
            });
        },
        navigate(url) {
            return this.perform(async () => {
                if (await this.flushDraft()) this.redirect(url);
            });
        },
        redirect(url) {
            window.location.assign(url);
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('cartQuantityUpdates', cartQuantityUpdates);
});
