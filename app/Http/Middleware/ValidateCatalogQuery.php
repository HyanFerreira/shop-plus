<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ValidateCatalogQuery
{
    public function handle(Request $request, Closure $next): Response
    {
        // URL values are applied to typed Livewire properties before component validation.
        $validator = Validator::make($request->query(), [
            'q' => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:120'],
            'selectedCategories' => ['array', 'max:50'],
            'selectedCategories.*' => ['required', 'string', 'max:120'],
            'brands' => ['array', 'max:50'],
            'brands.*' => ['required', 'string', 'max:120'],
            'minPrice' => ['nullable', 'numeric', 'between:0,9999999999'],
            'maxPrice' => ['nullable', 'numeric', 'between:0,9999999999'],
            'minimumRating' => ['integer', 'between:0,5'],
            'sort' => ['string', 'in:relevance,name,price_asc,price_desc,rating'],
            'perPage' => ['integer', 'between:1,100'],
            'page' => ['integer', 'between:1,10000'],
            'inStock' => ['in:0,1,true,false'],
            'freeShipping' => ['in:0,1,true,false'],
            'expressShipping' => ['in:0,1,true,false'],
        ]);
        abort_if($validator->fails(), 422, 'Filtros de catálogo inválidos.');

        return $next($request);
    }
}
