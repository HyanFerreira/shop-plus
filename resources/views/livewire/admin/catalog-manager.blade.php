<div class="space-y-8">
    @if (session('catalogMessage'))
        <p class="rounded-md bg-green-50 p-4 text-sm text-green-800" role="status">{{ session('catalogMessage') }}</p>
    @endif

    <section class="bg-white shadow-xl sm:rounded-lg" aria-labelledby="categories-title">
        <div class="p-6 space-y-6">
            <div>
                <h3 id="categories-title" class="text-lg font-medium text-gray-900">Categorias</h3>
                <p class="mt-1 text-sm text-gray-600">Organize os produtos disponíveis na loja.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead><tr class="text-left text-xs uppercase tracking-wide text-gray-500"><th class="py-3">Nome</th><th>Status</th><th class="text-right">Ações</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($categories as $category)
                            <tr wire:key="category-{{ $category->id }}" @class(['opacity-60' => $category->trashed()])>
                                <td class="py-3 pe-4"><p class="font-medium text-gray-900">{{ $category->name }}</p><p class="text-sm text-gray-500">{{ $category->description }}</p></td>
                                <td class="pe-4 text-sm text-gray-600">{{ $category->trashed() ? 'Removida' : ($category->status === \App\Enums\CatalogStatus::Active ? 'Ativa' : 'Inativa') }}</td>
                                <td class="py-3 text-right">
                                    @if ($category->trashed())
                                        <x-secondary-button type="button" wire:click="restoreCategory({{ $category->id }})">Restaurar</x-secondary-button>
                                    @else
                                        <x-secondary-button type="button" wire:click="editCategory({{ $category->id }})">Editar</x-secondary-button>
                                        <x-danger-button type="button" class="ms-2" wire:click="deleteCategory({{ $category->id }})" wire:confirm="Remover esta categoria?">Remover</x-danger-button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-sm text-gray-500">Nenhuma categoria cadastrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <form wire:submit="saveCategory" class="border-t border-gray-200 pt-6 space-y-5">
                <h4 class="font-medium text-gray-900">{{ $categoryId ? 'Editar categoria' : 'Nova categoria' }}</h4>
                <div>
                    <x-label for="categoryName" value="Nome" />
                    <x-input id="categoryName" class="mt-1 block w-full" wire:model="categoryName" />
                    <x-input-error for="categoryName" class="mt-2" />
                </div>
                <div>
                    <x-label for="categoryDescription" value="Descrição" />
                    <textarea id="categoryDescription" wire:model="categoryDescription" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    <x-input-error for="categoryDescription" class="mt-2" />
                </div>
                <div>
                    <x-label for="categoryStatus" value="Status" />
                    <select id="categoryStatus" wire:model="categoryStatus" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="active">Ativa</option><option value="inactive">Inativa</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3">
                    @if ($categoryId)<x-secondary-button type="button" wire:click="cancelCategoryEdit">Cancelar</x-secondary-button>@endif
                    <x-button type="submit">Salvar categoria</x-button>
                </div>
            </form>
        </div>
    </section>
</div>
