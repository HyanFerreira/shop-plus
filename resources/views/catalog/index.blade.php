<x-guest-layout>
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('catalog.index') }}" class="text-xl font-semibold text-gray-900">Sistema de Comércio</a>
            <nav class="flex gap-4 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}" class="text-indigo-700 hover:text-indigo-900">Painel</a>
                @else
                    <a href="{{ route('login') }}" class="text-indigo-700 hover:text-indigo-900">Entrar</a>
                @endauth
            </nav>
        </div>
    </header>
    <main class="min-h-screen bg-gray-50 py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h1 class="mb-8 text-3xl font-bold text-gray-900">Catálogo</h1>
            <livewire:catalog.browser />
        </div>
    </main>
</x-guest-layout>
