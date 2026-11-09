<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Page title, description and share image for search engines and social previews.
 * Pages set what they know; the shop layout fills in the rest from the shop settings.
 */
class Seo
{
    public ?string $title = null;

    public ?string $description = null;

    public ?string $image = null;

    public string $type = 'website';

    public bool $noindex = false;

    public static function set(?string $title = null, ?string $description = null, ?string $image = null, string $type = 'website'): self
    {
        $seo = app(self::class);
        $seo->title = $title ?? $seo->title;
        $seo->description = $description ?? $seo->description;
        $seo->image = $image ?? $seo->image;
        $seo->type = $type;

        return $seo;
    }

    public function fullTitle(?string $fallback = null): string
    {
        $store = (string) Setting::get('store_name');
        $title = $this->title ?? $fallback;

        return filled($title) ? $title.' | '.$store : $store.' – '.Setting::get('tagline');
    }

    public function metaDescription(): string
    {
        return str((string) ($this->description ?? Setting::get('meta_description')))->squish()->limit(160)->toString();
    }

    public function shareImage(): string
    {
        return $this->image ?? asset('images/og-image.jpg');
    }
}
