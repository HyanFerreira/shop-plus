@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginação do catálogo" class="isolate inline-flex overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" class="inline-flex size-9 items-center justify-center border-r border-slate-200 text-slate-300"><x-icon name="arrow-left" class="size-4" /></span>
        @else
            <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" rel="prev" aria-label="Página anterior" class="inline-flex size-9 items-center justify-center border-r border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-blue-600"><x-icon name="arrow-left" class="size-4" /></button>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="inline-flex size-9 items-center justify-center border-r border-slate-200 text-xs text-slate-400">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="inline-flex size-9 items-center justify-center border-r border-blue-600 bg-blue-600 text-xs font-bold text-white">{{ $page }}</span>
                    @else
                        <button type="button" wire:click="setPage({{ $page }}, '{{ $paginator->getPageName() }}')" class="inline-flex size-9 items-center justify-center border-r border-slate-200 text-xs font-semibold text-slate-600 transition hover:bg-blue-50 hover:text-blue-700">{{ $page }}</button>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" rel="next" aria-label="Próxima página" class="inline-flex size-9 items-center justify-center text-slate-500 transition hover:bg-slate-50 hover:text-blue-600"><x-icon name="arrow-left" class="size-4 rotate-180" /></button>
        @else
            <span aria-disabled="true" class="inline-flex size-9 items-center justify-center text-slate-300"><x-icon name="arrow-left" class="size-4 rotate-180" /></span>
        @endif
    </nav>
@endif
