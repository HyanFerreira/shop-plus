<div class="mt-8">
    <form wire:submit="add" class="flex items-end gap-3">
        <div>
            <label for="quantity" class="block text-sm font-medium text-gray-700">Quantidade</label>
            <input id="quantity" type="number" min="1" max="99" wire:model="quantity" class="mt-1 w-24 rounded-md border-gray-300 shadow-sm">
        </div>
        <button type="submit" class="rounded-md bg-indigo-600 px-5 py-2.5 font-semibold text-white hover:bg-indigo-500">Adicionar ao carrinho</button>
    </form>
    @error('quantity') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
    @error('product') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
