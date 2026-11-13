<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\Models\Category;
use App\Models\ProductImage;
use App\Support\ImageOptimizer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

#[Signature('app:localise-images')]
#[Description('Download sample photos that still come from other websites and save them on this site as small WebP files')]
class LocaliseImages extends Command
{
    public function handle(ImageOptimizer $optimizer): int
    {
        $jobs = [
            ['products', ProductImage::query()->where('path', 'like', 'http%')->get(), 'path'],
            ['categories', Category::query()->where('image', 'like', 'http%')->get(), 'image'],
            ['banners', Banner::query()->where('image', 'like', 'http%')->get(), 'image'],
        ];

        $saved = 0;
        $failed = 0;

        foreach ($jobs as [$directory, $models, $column]) {
            /** @var Model $model */
            foreach ($models as $model) {
                $url = $this->jpegUrl((string) $model->getAttribute($column));

                try {
                    $model->forceFill([$column => $optimizer->storeFromUrl($url, $directory)])->save();
                    $saved++;
                    $this->components->task(Str::limit($url, 70), fn () => true);
                } catch (Throwable $e) {
                    $failed++;
                    $this->components->warn("Skipped {$url}: {$e->getMessage()}");
                }
            }
        }

        $this->components->info("{$saved} photos are now stored on this site".($failed ? ", {$failed} could not be downloaded (they still load from the web)." : '.'));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Ask for a plain JPEG at a sensible size, which PHP's GD can always read.
     */
    private function jpegUrl(string $url): string
    {
        if (! str_contains($url, 'images.unsplash.com')) {
            return $url;
        }

        $url = Str::replaceMatches('/([?&])auto=format/', '$1fm=jpg', $url);
        $url = Str::replaceMatches('/([?&])w=\d+/', '${1}w=1600', $url);

        return str_contains($url, 'fm=jpg') ? $url : $url.'&fm=jpg';
    }
}
