<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DemoProductImagesSeeder extends Seeder
{
    public function run(): void
    {
        $disk = Storage::disk('public');

        foreach (config('demo-images.products', []) as $sku => $image) {
            $product = Product::query()->where('sku', $sku)->first();
            if (! $product) {
                continue;
            }

            $source = public_path('images/demo-products/'.$image['file']);
            if (! is_file($source)) {
                throw new RuntimeException('Demo illustration missing: '.$image['file']);
            }

            $path = 'products/demo/'.$image['file'];
            if (! $disk->exists($path) && ! $disk->put($path, file_get_contents($source))) {
                throw new RuntimeException('Could not store demo illustration: '.$image['file']);
            }

            // Keep existing photos and all commercial data. Repeat runs add no duplicates.
            $product->images()->firstOrCreate(
                ['path' => $path],
                ['alt_text' => $image['alt'], 'sort_order' => 100],
            );
        }
    }
}
