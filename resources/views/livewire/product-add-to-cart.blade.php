<div class="mt-5">
    <form wire:submit="add" class="flex flex-col gap-3 sm:flex-row">
        <div class="inline-flex h-12 items-center overflow-hidden rounded-lg border border-slate-200 bg-white"><button type="button" wire:click="decrement" class="flex size-12 items-center justify-center text-lg text-slate-600 hover:bg-slate-50">−</button><input aria-label="Quantidade" type="number" min="1" max="99" wire:model="quantity" class="h-full w-10 border-x border-slate-200 text-center text-sm font-bold outline-none"><button type="button" wire:click="increment" class="flex size-12 items-center justify-center text-lg text-slate-600 hover:bg-slate-50">+</button></div>
        <button type="submit" wire:loading.attr="disabled" class="flex h-12 flex-1 items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50"><x-icon name="shopping-cart" class="size-5" />Adicionar ao carrinho</button>
    </form>
    <a href="{{ route('checkout.show') }}" class="mt-3 flex h-11 items-center justify-center rounded-lg border border-slate-200 text-sm font-bold text-slate-800 transition hover:bg-slate-50">Comprar agora</a>
    <x-input-error for="quantity" />
    <x-input-error for="product" />
</div>
