<aside class="hidden w-[285px] shrink-0 flex-col justify-between overflow-hidden rounded-l-2xl bg-gradient-to-br from-blue-950 via-blue-900 to-slate-950 p-9 text-white md:flex">
    <div>
        <x-icon name="shopping-bag" class="size-12" stroke-width="1.7" />
        <h1 class="mt-5 text-3xl font-black tracking-tight">ShopPlus</h1>
        <p class="mt-2 text-base text-blue-100">Sua loja online completa</p>
        <ul class="mt-9 space-y-4 text-sm text-slate-100">
            @foreach (['Produtos de qualidade', 'Entrega em todo o Brasil', 'Pagamentos seguros', 'Suporte especializado'] as $benefit)
                <li class="flex items-center gap-3"><x-icon name="check" class="size-5 text-white" stroke-width="2.5" />{{ $benefit }}</li>
            @endforeach
        </ul>
    </div>
    <div class="flex items-end justify-center gap-2 opacity-90"><span class="h-16 w-12 rounded-t-xl bg-blue-400 shadow-lg"></span><span class="h-24 w-14 rounded-t-2xl bg-blue-600 shadow-lg"></span><span class="h-20 w-12 rounded-t-xl bg-rose-400 shadow-lg"></span></div>
</aside>
