@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $pages = $last <= 7
            ? range(1, $last)
            : array_values(array_unique([1, ...range(max(2, min($current - 1, $last - 2)), min($last - 1, max(3, $current + 1))), $last]));
        $compactPages = $last <= 3 ? range(1, $last) : array_values(array_unique([1, $current === 1 ? 2 : ($current === $last ? $last - 1 : $current), $last]));
    @endphp
    <nav aria-label="Paginação do catálogo" class="catalog-pagination">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" aria-label="Página anterior" class="catalog-page-button"><x-icon name="arrow-left" class="size-4" /></span>
        @else
            <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" rel="prev" aria-label="Página anterior" class="catalog-page-button"><x-icon name="arrow-left" class="size-4" /></button>
        @endif

        @foreach (['wide' => $pages, 'compact' => $compactPages] as $window => $windowPages)
            <span data-pagination-window="{{ $window }}" class="catalog-pagination__window">
                @php $previous = 0; @endphp
                @foreach ($windowPages as $page)
                    @if ($window === 'wide' && $page - $previous === 2)
                        <button type="button" wire:click="setPage({{ $page - 1 }}, '{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" aria-label="Página {{ $page - 1 }}" class="catalog-page-button">{{ $page - 1 }}</button>
                    @elseif ($page - $previous > 1)
                        <span class="catalog-page-ellipsis" aria-hidden="true">…</span>
                    @endif
                    @if ($page === $current)
                        <span aria-current="page" aria-label="Página {{ $page }}" class="catalog-page-button">{{ $page }}</span>
                    @else
                        <button type="button" wire:click="setPage({{ $page }}, '{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" aria-label="Página {{ $page }}" class="catalog-page-button">{{ $page }}</button>
                    @endif
                    @php $previous = $page; @endphp
                @endforeach
            </span>
        @endforeach

        @if ($paginator->hasMorePages())
            <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" rel="next" aria-label="Próxima página" class="catalog-page-button"><x-icon name="arrow-left" class="size-4 rotate-180" /></button>
        @else
            <span aria-disabled="true" aria-label="Próxima página" class="catalog-page-button"><x-icon name="arrow-left" class="size-4 rotate-180" /></span>
        @endif
    </nav>
@endif
