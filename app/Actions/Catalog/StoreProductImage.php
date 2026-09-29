<?php

namespace App\Actions\Catalog;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Support\SecurityAudit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class StoreProductImage
{
    public function execute(
        User $actor,
        int $productId,
        UploadedFile $upload,
        ?string $altText,
        int $sortOrder,
    ): ProductImage {
        abort_unless($actor->isAdmin(), 403);

        $product = Product::query()->findOrFail($productId);
        $path = $upload->storePublicly('products/'.$product->id, 'public');

        try {
            $image = $product->images()->create([
                'path' => $path,
                'alt_text' => $altText ? trim($altText) : null,
                'sort_order' => $sortOrder,
            ]);
            app(SecurityAudit::class)->record($actor, 'product.image_created', $image);

            return $image;
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }
    }
}
