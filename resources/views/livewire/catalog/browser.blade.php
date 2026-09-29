<div>
    <div class="mb-8 grid gap-4 rounded-xl bg-white p-5 shadow-sm md:grid-cols-3">
        <div><x-label for="catalogSearch" value="Buscar" /><x-input id="catalogSearch" class="mt-1 block w-full" wire:model.live.debounce.300ms="search" placeholder="Nome ou descrição" /></div>
        <div><x-label for="catalogCategory" value="Categoria" /><select id="catalogCategory" wire:model.live="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="">Todas</option>@foreach ($categories as $item)<option value="{{ $item->slug }}">{{ $item->name }}</option>@endforeach</select></div>
        <div><x-label for="catalogSort" value="Ordenar" /><select id="catalogSort" wire:model.live="sort" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="newest">Mais recentes</option><option value="name">Nome</option><option value="price_asc">Menor preço</option><option value="price_desc">Maior preço</option></select></div>
    </div>

    @if ($products->isEmpty())
        <p class="rounded-xl bg-white p-10 text-center text-gray-500">Nenhum produto encontrado.</p>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($products as $product)
                <article wire:key="catalog-product-{{ $product->id }}" class="overflow-hidden rounded-xl bg-white shadow-sm">
                    @if ($product->images->isNotEmpty())
                        <img src="{{ Storage::disk('public')->url($product->images->first()->path) }}" alt="{{ $product->images->first()->alt_text }}" class="aspect-square w-full object-cover">
                    @else
                        <div class="flex aspect-square items-center justify-center bg-gray-200 text-sm text-gray-500">Sem imagem</div>
                    @endif
                    <div class="p-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-indigo-700">{{ $product->category->name }}</p>
                        <h2 class="mt-2 font-semibold text-gray-900"><a href="{{ route('catalog.show', $product->slug) }}" class="hover:text-indigo-700">{{ $product->name }}</a></h2>
                        <p class="mt-3 text-lg font-bold text-gray-900">{{ \App\Domain\Catalog\Price::fromCents($product->price_cents)->brl() }}</p>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-8">{{ $products->links() }}</div>
    @endif
</div>
