@if ($paginator->hasPages())
    <nav aria-label="Paginação do catálogo" class="catalog-pagination">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" aria-label="Página anterior" class="catalog-page-button"><x-icon name="arrow-left" class="size-4" /></span>
        @else
            <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" rel="prev" aria-label="Página anterior" class="catalog-page-button"><x-icon name="arrow-left" class="size-4" /></button>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="catalog-page-ellipsis">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" aria-label="Página {{ $page }}" class="catalog-page-button">{{ $page }}</span>
                    @else
                        <button type="button" wire:click="setPage({{ $page }}, '{{ $paginator->getPageName() }}')" aria-label="Página {{ $page }}" class="catalog-page-button">{{ $page }}</button>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" rel="next" aria-label="Próxima página" class="catalog-page-button"><x-icon name="arrow-left" class="size-4 rotate-180" /></button>
        @else
            <span aria-disabled="true" aria-label="Próxima página" class="catalog-page-button"><x-icon name="arrow-left" class="size-4 rotate-180" /></span>
        @endif
    </nav>
@endif
