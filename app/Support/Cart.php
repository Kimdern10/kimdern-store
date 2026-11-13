<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * The shopping cart, kept in the visitor's session so guests can shop without an account.
 * Only product ids and quantities are stored; prices and stock always come fresh from the database.
 */
class Cart
{
    private const string LINES = 'cart.lines';

    private const string COUPON = 'cart.coupon';

    public const int MAX_PER_LINE = 20;

    /** @var Collection<int, CartLine>|null */
    private ?Collection $cached = null;

    public function __construct(private readonly Session $session) {}

    public static function key(int $productId, ?int $variantId = null): string
    {
        return $productId.':'.($variantId ?? 0);
    }

    /**
     * Put a product in the cart (or add to the quantity already there).
     *
     * @throws ValidationException when the size/colour is missing or there isn't enough stock.
     */
    public function add(Product $product, ?ProductVariant $variant = null, int $quantity = 1): void
    {
        if (! $product->is_active) {
            throw ValidationException::withMessages(['cart' => 'This product is no longer available.']);
        }

        if ($product->variants()->exists() && (! $variant || $variant->product_id !== $product->id)) {
            throw ValidationException::withMessages(['variant' => 'Please choose an option first.']);
        }

        if (! $product->variants()->exists()) {
            $variant = null;
        }

        $key = self::key($product->id, $variant?->id);
        $lines = $this->raw();
        $wanted = ($lines[$key] ?? 0) + max(1, $quantity);
        $available = $variant ? $variant->stock : $product->stock;

        if ($available < 1) {
            throw ValidationException::withMessages(['cart' => 'Sorry, this item is out of stock.']);
        }

        if ($wanted > $available) {
            throw ValidationException::withMessages(['cart' => "Only {$available} left in stock."]);
        }

        $lines[$key] = min($wanted, self::MAX_PER_LINE);
        $this->store($lines);
    }

    public function update(string $key, int $quantity): void
    {
        $lines = $this->raw();

        if (! isset($lines[$key])) {
            return;
        }

        if ($quantity < 1) {
            unset($lines[$key]);
        } else {
            $line = $this->lines()->firstWhere('key', $key);
            $lines[$key] = min($quantity, self::MAX_PER_LINE, $line ? max(1, $line->available()) : $quantity);
        }

        $this->store($lines);
    }

    public function remove(string $key): void
    {
        $this->update($key, 0);
    }

    public function clear(): void
    {
        $this->session->forget([self::LINES, self::COUPON]);
        $this->cached = null;
    }

    /**
     * What is in the cart, with products loaded. Lines for products that were removed
     * or have sold out since are dropped, and quantities are capped at what's in stock.
     *
     * @return Collection<int, CartLine>
     */
    public function lines(): Collection
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $raw = $this->raw();

        if ($raw === []) {
            return $this->cached = collect();
        }

        $ids = collect(array_keys($raw))->map(fn (string $key) => array_map('intval', explode(':', $key)));
        $products = Product::query()->active()->with(['mainImage', 'variants'])->whereIn('id', $ids->pluck(0))->get()->keyBy('id');

        $lines = collect();
        $clean = [];

        foreach ($raw as $key => $quantity) {
            [$productId, $variantId] = array_map('intval', explode(':', (string) $key));
            $product = $products->get($productId);
            $variant = $variantId ? $product?->variants->firstWhere('id', $variantId) : null;

            if (! $product || ($variantId && ! $variant) || (! $variantId && $product->variants->isNotEmpty())) {
                continue;
            }

            $available = $variant ? $variant->stock : $product->stock;

            if ($available < 1) {
                continue;
            }

            $quantity = min((int) $quantity, $available, self::MAX_PER_LINE);
            $clean[$key] = $quantity;
            $lines->push(new CartLine((string) $key, $product, $variant, $quantity));
        }

        if ($clean !== $raw) {
            $this->session->put(self::LINES, $clean);
        }

        return $this->cached = $lines;
    }

    public function count(): int
    {
        return (int) $this->lines()->sum('quantity');
    }

    public function isEmpty(): bool
    {
        return $this->lines()->isEmpty();
    }

    public function subtotal(): int
    {
        return (int) $this->lines()->sum(fn (CartLine $line) => $line->total());
    }

    /**
     * Apply a coupon code. Returns a message saying why it can't be used, or null on success.
     */
    public function applyCoupon(string $code, ?string $email = null, ?string $phone = null, ?int $userId = null): ?string
    {
        $coupon = Coupon::findByCode($code);

        if (! $coupon) {
            return 'That code is not valid.';
        }

        if ($problem = $coupon->problemWith($this->subtotal()) ?? $coupon->problemForCustomer($email, $phone, $userId)) {
            return $problem;
        }

        $this->session->put(self::COUPON, $coupon->code);

        return null;
    }

    public function removeCoupon(): void
    {
        $this->session->forget(self::COUPON);
    }

    /**
     * The applied coupon, if it can still be used on the cart as it is now.
     */
    public function coupon(): ?Coupon
    {
        $coupon = Coupon::findByCode($this->session->get(self::COUPON));

        return $coupon && $coupon->problemWith($this->subtotal()) === null ? $coupon : null;
    }

    public function discount(): int
    {
        return $this->coupon()?->discountFor($this->subtotal()) ?? 0;
    }

    /**
     * Money totals for the cart, with delivery worked out for the given state.
     *
     * @return array{subtotal: int, discount: int, delivery_fee: int|null, total: int, zone: DeliveryZone|null}
     */
    public function totals(?string $state = null): array
    {
        $subtotal = $this->subtotal();
        $discount = $this->discount();
        $zone = DeliveryZone::forState($state);
        $fee = $zone?->feeFor($subtotal - $discount);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'delivery_fee' => $fee,
            'total' => $subtotal - $discount + ($fee ?? 0),
            'zone' => $zone,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function raw(): array
    {
        $lines = $this->session->get(self::LINES, []);

        return is_array($lines) ? $lines : [];
    }

    /**
     * @param  array<string, int>  $lines
     */
    private function store(array $lines): void
    {
        $this->session->put(self::LINES, $lines);
        $this->cached = null;
    }
}
