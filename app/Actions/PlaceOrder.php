<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Mail\NewOrderAlert;
use App\Mail\OrderPlaced;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Support\Cart;
use App\Support\CartLine;
use App\Support\Mailer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turns the cart into an order: checks stock again (locking the rows so two
 * shoppers can't buy the last item), saves the order and takes the items out of stock.
 */
class PlaceOrder
{
    public function __construct(private readonly Cart $cart) {}

    /**
     * @param  array{email: string, name: string, phone: string, address: string, city: string, state: string, notes?: string|null}  $details
     * @param  'paystack'|'bank_transfer'  $paymentMethod
     *
     * @throws ValidationException when the cart is empty, something sold out or the state isn't delivered to.
     */
    public function handle(array $details, string $paymentMethod, ?User $user = null): Order
    {
        $lines = $this->cart->lines();

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }

        $totals = $this->cart->totals($details['state']);

        if (! $totals['zone']) {
            throw ValidationException::withMessages(['state' => 'Sorry, we do not deliver to this state yet.']);
        }

        $coupon = $this->cart->coupon();

        if ($coupon && ($problem = $coupon->problemForCustomer($details['email'], $details['phone'], $user?->id))) {
            $this->cart->removeCoupon();

            throw ValidationException::withMessages([
                'cart' => $problem.' We have taken the code off your order. Please check the new total and place your order again.',
            ]);
        }

        $order = DB::transaction(function () use ($lines, $details, $paymentMethod, $user, $totals, $coupon): Order {
            $this->reserveStock($lines);

            $order = Order::query()->create([
                'number' => Order::newNumber(),
                'user_id' => $user?->id,
                'email' => strtolower($details['email']),
                'name' => $details['name'],
                'phone' => $details['phone'],
                'address' => $details['address'],
                'city' => $details['city'],
                'state' => $details['state'],
                'notes' => $details['notes'] ?? null,
                'delivery_zone_id' => $totals['zone']->id,
                'delivery_eta' => $totals['zone']->eta,
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'delivery_fee' => $totals['delivery_fee'] ?? 0,
                'total' => $totals['total'],
                'coupon_code' => $coupon?->code,
                'status' => OrderStatus::Pending,
                'payment_method' => $paymentMethod,
            ]);

            $order->items()->createMany($lines->map(fn (CartLine $line) => [
                'product_id' => $line->product->id,
                'product_variant_id' => $line->variant?->id,
                'name' => $line->product->name,
                'variant_label' => $line->variantLabel(),
                'image' => $line->product->mainImage?->path,
                'price' => $line->unitPrice(),
                'quantity' => $line->quantity,
                'line_total' => $line->total(),
            ])->all());

            $order->history()->create(['status' => OrderStatus::Pending, 'note' => 'Order placed.']);

            if ($coupon) {
                Coupon::query()->whereKey($coupon->id)->increment('used_count');
            }

            return $order;
        });

        $this->cart->clear();

        // Card payments are confirmed by email once Paystack says they are paid.
        if ($paymentMethod === 'bank_transfer') {
            $order->load('items');
            Mailer::send($order->email, new OrderPlaced($order));
            Mailer::send((string) Setting::get('email'), new NewOrderAlert($order));
        }

        return $order;
    }

    /**
     * @param  Collection<int, CartLine>  $lines
     */
    private function reserveStock(Collection $lines): void
    {
        foreach ($lines as $line) {
            $row = $line->variant
                ? ProductVariant::query()->lockForUpdate()->find($line->variant->id)
                : Product::query()->lockForUpdate()->find($line->product->id);

            if (! $row || $row->stock < $line->quantity) {
                throw ValidationException::withMessages([
                    'cart' => "Sorry, {$line->product->name} just sold out or has fewer left than you wanted. Please check your cart.",
                ]);
            }

            $row->decrement('stock', $line->quantity);
            Product::query()->whereKey($line->product->id)->increment('sales_count', $line->quantity);
        }
    }
}
