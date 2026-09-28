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

    <section class="bg-white shadow-xl sm:rounded-lg" aria-labelledby="products-title">
        <div class="p-6 space-y-6">
            <div>
                <h3 id="products-title" class="text-lg font-medium text-gray-900">Produtos</h3>
                <p class="mt-1 text-sm text-gray-600">Valores são persistidos em centavos inteiros.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead><tr class="text-left text-xs uppercase tracking-wide text-gray-500"><th class="py-3">Produto</th><th>Categoria</th><th>Preço</th><th>Status</th><th class="text-right">Ações</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($products as $product)
                            <tr wire:key="product-{{ $product->id }}" @class(['opacity-60' => $product->trashed()])>
                                <td class="py-3 pe-4"><p class="font-medium text-gray-900">{{ $product->name }}</p><p class="text-sm text-gray-500">{{ $product->sku }} · {{ $product->description }}</p></td>
                                <td class="pe-4 text-sm text-gray-600">{{ $product->category->name }}</td>
                                <td class="pe-4 text-sm text-gray-900">{{ \App\Domain\Catalog\Price::fromCents($product->price_cents)->brl() }}</td>
                                <td class="pe-4 text-sm text-gray-600">{{ $product->trashed() ? 'Removido' : ($product->status === \App\Enums\CatalogStatus::Active ? 'Ativo' : 'Inativo') }}</td>
                                <td class="py-3 text-right">
                                    @if ($product->trashed())
                                        <x-secondary-button type="button" wire:click="restoreProduct({{ $product->id }})">Restaurar</x-secondary-button>
                                    @else
                                        <x-secondary-button type="button" wire:click="editProduct({{ $product->id }})">Editar</x-secondary-button>
                                        <x-danger-button type="button" class="ms-2" wire:click="deleteProduct({{ $product->id }})" wire:confirm="Remover este produto?">Remover</x-danger-button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-sm text-gray-500">Nenhum produto cadastrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <form wire:submit="saveProduct" class="border-t border-gray-200 pt-6 space-y-5">
                <h4 class="font-medium text-gray-900">{{ $productId ? 'Editar produto' : 'Novo produto' }}</h4>
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <x-label for="productCategoryId" value="Categoria" />
                        <select id="productCategoryId" wire:model="productCategoryId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Selecione</option>
                            @foreach ($categories->whereNull('deleted_at') as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="productCategoryId" class="mt-2" />
                    </div>
                    <div><x-label for="productName" value="Nome" /><x-input id="productName" class="mt-1 block w-full" wire:model="productName" /><x-input-error for="productName" class="mt-2" /></div>
                    <div><x-label for="productSku" value="SKU" /><x-input id="productSku" class="mt-1 block w-full" wire:model="productSku" /><x-input-error for="productSku" class="mt-2" /></div>
                    <div><x-label for="productPrice" value="Preço (R$)" /><x-input id="productPrice" class="mt-1 block w-full" wire:model="productPrice" inputmode="decimal" placeholder="0,00" /><x-input-error for="productPrice" class="mt-2" /></div>
                    <div><x-label for="productWeight" value="Peso (g)" /><x-input id="productWeight" type="number" min="1" class="mt-1 block w-full" wire:model="productWeight" /><x-input-error for="productWeight" class="mt-2" /></div>
                    <div><x-label for="productStatus" value="Status" /><select id="productStatus" wire:model="productStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="active">Ativo</option><option value="inactive">Inativo</option></select></div>
                    <div><x-label for="productWidth" value="Largura (mm)" /><x-input id="productWidth" type="number" min="1" class="mt-1 block w-full" wire:model="productWidth" /><x-input-error for="productWidth" class="mt-2" /></div>
                    <div><x-label for="productHeight" value="Altura (mm)" /><x-input id="productHeight" type="number" min="1" class="mt-1 block w-full" wire:model="productHeight" /><x-input-error for="productHeight" class="mt-2" /></div>
                    <div><x-label for="productLength" value="Comprimento (mm)" /><x-input id="productLength" type="number" min="1" class="mt-1 block w-full" wire:model="productLength" /><x-input-error for="productLength" class="mt-2" /></div>
                </div>
                <div><x-label for="productDescription" value="Descrição" /><textarea id="productDescription" wire:model="productDescription" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea><x-input-error for="productDescription" class="mt-2" /></div>
                <div class="flex justify-end gap-3">
                    @if ($productId)<x-secondary-button type="button" wire:click="cancelProductEdit">Cancelar</x-secondary-button>@endif
                    <x-button type="submit">Salvar produto</x-button>
                </div>
            </form>
        </div>
    </section>

    <section class="bg-white shadow-xl sm:rounded-lg" aria-labelledby="images-title">
        <div class="p-6 space-y-6">
            <div>
                <h3 id="images-title" class="text-lg font-medium text-gray-900">Imagens dos produtos</h3>
                <p class="mt-1 text-sm text-gray-600">JPEG, PNG ou WebP, com no máximo 2 MB.</p>
            </div>

            <div class="space-y-5">
                @foreach ($products->whereNull('deleted_at') as $product)
                    @if ($product->images->isNotEmpty())
                        <div wire:key="product-images-{{ $product->id }}">
                            <h4 class="mb-2 font-medium text-gray-900">{{ $product->name }}</h4>
                            <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($product->images as $image)
                                    <li wire:key="image-{{ $image->id }}" class="rounded-md border border-gray-200 p-3">
                                        <img src="{{ Storage::disk('public')->url($image->path) }}" alt="{{ $image->alt_text }}" class="h-28 w-full rounded object-cover">
                                        <p class="mt-2 text-sm text-gray-600">{{ $image->alt_text ?: 'Sem texto alternativo' }}</p>
                                        <p class="text-xs text-gray-500">Ordem {{ $image->sort_order }}</p>
                                        <x-danger-button type="button" class="mt-2" wire:click="deleteProductImage({{ $image->id }})" wire:confirm="Remover esta imagem?">Remover</x-danger-button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            </div>

            <form wire:submit="saveProductImage" class="border-t border-gray-200 pt-6 space-y-5">
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <x-label for="imageProductId" value="Produto" />
                        <select id="imageProductId" wire:model="imageProductId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Selecione</option>
                            @foreach ($products->whereNull('deleted_at') as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach
                        </select>
                        <x-input-error for="imageProductId" class="mt-2" />
                    </div>
                    <div>
                        <x-label for="productImage" value="Arquivo" />
                        <input id="productImage" type="file" wire:model="productImage" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-gray-700">
                        <x-input-error for="productImage" class="mt-2" />
                    </div>
                    <div><x-label for="imageAltText" value="Texto alternativo" /><x-input id="imageAltText" class="mt-1 block w-full" wire:model="imageAltText" /><x-input-error for="imageAltText" class="mt-2" /></div>
                    <div><x-label for="imageSortOrder" value="Ordem" /><x-input id="imageSortOrder" type="number" min="0" class="mt-1 block w-full" wire:model="imageSortOrder" /><x-input-error for="imageSortOrder" class="mt-2" /></div>
                </div>
                <div class="flex justify-end"><x-button type="submit" wire:loading.attr="disabled" wire:target="productImage,saveProductImage">Salvar imagem</x-button></div>
            </form>
        </div>
    </section>
</div>
