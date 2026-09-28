<?php

namespace App\Actions\Catalog;

use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class DeleteProductImage
{
    public function execute(User $actor, int $imageId): void
    {
        abort_unless($actor->isAdmin(), 403);

        $image = ProductImage::query()->findOrFail($imageId);
        Storage::disk('public')->delete($image->path);
        $image->delete();
    }
}
