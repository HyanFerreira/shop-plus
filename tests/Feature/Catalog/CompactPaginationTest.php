<?php

namespace Tests\Feature\Catalog;

use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CompactPaginationTest extends TestCase
{
    public static function windows(): array
    {
        return [
            'first' => [13, 1, [1, 2, 3, 13], 1, [1, 2, 13], 1],
            'middle' => [13, 7, [1, 6, 7, 8, 13], 2, [1, 7, 13], 2],
            'last' => [13, 13, [1, 11, 12, 13], 1, [1, 12, 13], 1],
            'few' => [5, 3, [1, 2, 3, 4, 5], 0, [1, 3, 5], 2],
            'near beginning' => [13, 4, [1, 2, 3, 4, 5, 13], 1, [1, 4, 13], 2],
        ];
    }

    #[DataProvider('windows')]
    public function test_window_keeps_current_and_boundaries_without_invalid_destinations(int $last, int $current, array $expected, int $gaps, array $compact, int $compactGaps): void
    {
        $paginator = new LengthAwarePaginator([], $last * 12, 12, $current, ['pageName' => 'catalogPage']);
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$paginator->links('components.catalog-pagination')->toHtml());
        $xpath = new \DOMXPath($document);
        foreach (['wide' => [$expected, $gaps], 'compact' => [$compact, $compactGaps]] as $window => [$expectedPages, $expectedGaps]) {
            $scope = '//*[@data-pagination-window="'.$window.'"]';
            $pages = [];
            foreach ($xpath->query($scope.'/*') as $control) {
                if (ctype_digit(trim($control->textContent))) {
                    $pages[] = (int) trim($control->textContent);
                }
            }
            $this->assertSame($expectedPages, $pages);
            $this->assertSame($expectedGaps, $xpath->query($scope.'/*[@class="catalog-page-ellipsis"]')->length);
        }
        $this->assertSame((string) $current, trim($xpath->query('//*[@aria-current="page"]')->item(0)->textContent));
        foreach ($xpath->query('//button') as $control) {
            $action = $control->getAttribute('wire:click');
            $this->assertStringContainsString("'catalogPage'", $action);
            if (preg_match('/setPage\((\d+),/', $action, $match)) {
                $this->assertContains((int) $match[1], $expected);
            }
        }
        $this->assertSame($current === 1 ? 1 : 0, $xpath->query('//*[@aria-disabled="true" and @aria-label="Página anterior"]')->length);
        $this->assertSame($current === $last ? 1 : 0, $xpath->query('//*[@aria-disabled="true" and @aria-label="Próxima página"]')->length);
    }

    public function test_single_page_has_no_pagination_controls(): void
    {
        $paginator = new LengthAwarePaginator([], 8, 12);
        $this->assertStringNotContainsString('<nav', $paginator->links('components.catalog-pagination')->toHtml());
    }
}
