<?php

namespace App\Models;

use App\Support\Media;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Schema;

/**
 * Shop details the owner can change in the admin (name, contacts, socials, bank details...).
 *
 * @property string $key
 * @property string|null $value
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    private const string CACHE_KEY = 'settings.values';

    /**
     * Every setting with its starting value.
     *
     * @var array<string, string|null>
     */
    public const array DEFAULTS = [
        'store_name' => 'Kimdern Store',
        'tagline' => 'Beauty & fashion, delivered across Nigeria',
        'email' => 'hello@kimdernstore.com',
        'phone' => '0803 000 0000',
        'whatsapp' => '2348030000000',
        'address' => 'Lekki Phase 1, Lagos',
        'instagram_url' => 'https://instagram.com/',
        'facebook_url' => 'https://facebook.com/',
        'tiktok_url' => 'https://tiktok.com/',
        'x_url' => null,
        'logo' => null,
        'announcement' => 'Free delivery in Lagos on orders above ₦50,000',
        'bank_name' => 'Your Bank Name',
        'bank_account_name' => 'Kimdern Store',
        'bank_account_number' => '0000000000',
        'low_stock_threshold' => '5',
        'flash_sale_title' => 'Flash sale',
        'flash_sale_ends_at' => null,
        'meta_description' => 'Shop skincare, makeup, hair care, fragrance and fashion at Kimdern Store. Genuine products, fast delivery across Nigeria, pay with card or bank transfer.',
    ];

    /**
     * @return array<string, string|null>
     */
    public static function values(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $saved = Schema::hasTable('settings') ? static::query()->pluck('value', 'key')->all() : [];

            return array_merge(self::DEFAULTS, array_intersect_key($saved, self::DEFAULTS));
        });
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::values()[$key] ?? $default;
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public static function put(array $values): void
    {
        foreach (array_intersect_key($values, self::DEFAULTS) as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * When the flash sale ends, if one is running now.
     */
    public static function flashSaleEndsAt(): ?CarbonInterface
    {
        $value = static::get('flash_sale_ends_at');

        if (blank($value)) {
            return null;
        }

        try {
            $endsAt = Date::parse($value);
        } catch (\Throwable) {
            return null;
        }

        return $endsAt->isFuture() ? $endsAt : null;
    }

    public static function logoUrl(): ?string
    {
        return Media::url(static::get('logo'));
    }

    public static function whatsappUrl(?string $message = null): ?string
    {
        $number = preg_replace('/\D+/', '', (string) static::get('whatsapp'));

        return $number ? 'https://wa.me/'.$number.($message ? '?text='.rawurlencode($message) : '') : null;
    }
}
