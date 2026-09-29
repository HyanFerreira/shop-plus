<x-guest-layout>
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('catalog.index') }}" class="text-xl font-semibold text-gray-900">Sistema de Comércio</a>
            <a href="{{ route('catalog.index') }}" class="text-sm text-indigo-700 hover:text-indigo-900">Voltar ao catálogo</a>
        </div>
    </header>
    <main class="min-h-screen bg-gray-50 py-10">
        <article class="mx-auto grid max-w-6xl gap-10 px-4 md:grid-cols-2 sm:px-6 lg:px-8">
            <div>
                @if ($product->images->isNotEmpty())
                    <img src="{{ Storage::disk('public')->url($product->images->first()->path) }}" alt="{{ $product->images->first()->alt_text }}" class="aspect-square w-full rounded-xl bg-white object-cover shadow">
                @else
                    <div class="flex aspect-square items-center justify-center rounded-xl bg-gray-200 text-gray-500">Sem imagem</div>
                @endif
            </div>
            <div>
                <p class="text-sm font-medium text-indigo-700">{{ $product->category->name }}</p>
                <h1 class="mt-2 text-3xl font-bold text-gray-900">{{ $product->name }}</h1>
                <p class="mt-2 text-sm text-gray-500">SKU {{ $product->sku }}</p>
                <p class="mt-6 text-3xl font-semibold text-gray-900">{{ \App\Domain\Catalog\Price::fromCents($product->price_cents)->brl() }}</p>
                <p class="mt-6 whitespace-pre-line text-gray-700">{{ $product->description }}</p>
                <dl class="mt-8 grid grid-cols-2 gap-4 rounded-lg bg-white p-4 text-sm shadow-sm">
                    <div><dt class="text-gray-500">Peso</dt><dd class="font-medium">{{ $product->weight_grams }} g</dd></div>
                    <div><dt class="text-gray-500">Dimensões</dt><dd class="font-medium">{{ $product->width_mm }} × {{ $product->height_mm }} × {{ $product->length_mm }} mm</dd></div>
                </dl>
            </div>
        </article>
    </main>
</x-guest-layout>
