<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Guest orders spread over the last month, for trying out the admin on a local copy.
 * No customer logins are created.
 */
class DemoOrderSeeder extends Seeder
{
    public function run(): void
    {
        if (Order::query()->exists()) {
            return;
        }

        $products = Product::query()->with(['variants', 'mainImage'])->get();
        $people = [
            ['Ifeoma Okafor', 'Lagos', 'Ikeja'], ['Bola Adeyemi', 'Oyo', 'Ibadan'], ['Musa Bello', 'FCT - Abuja', 'Wuse 2'],
            ['Chidi Nwosu', 'Anambra', 'Awka'], ['Grace Effiong', 'Akwa Ibom', 'Uyo'], ['Seun Martins', 'Lagos', 'Lekki'],
            ['Aisha Lawal', 'Kaduna', 'Barnawa'], ['Ejiro Okoro', 'Delta', 'Warri'], ['Kemi Balogun', 'Ogun', 'Abeokuta'],
            ['Obinna Eze', 'Enugu', 'Independence Layout'], ['Fatima Yusuf', 'Kano', 'Nasarawa GRA'], ['Tobi Ade', 'Lagos', 'Yaba'],
            ['Ruth Danjuma', 'Plateau', 'Jos'], ['Uche Obi', 'Rivers', 'GRA Phase 2'],
        ];
        $statuses = [OrderStatus::Delivered, OrderStatus::Delivered, OrderStatus::Shipped, OrderStatus::Processing, OrderStatus::Paid, OrderStatus::Pending, OrderStatus::Delivered, OrderStatus::Cancelled];

        foreach ($people as $i => [$name, $state, $city]) {
            $status = $statuses[$i % count($statuses)];
            $zone = DeliveryZone::forState($state);
            $lines = $products->shuffle()->take(1 + $i % 3);
            $subtotal = 0;
            $items = [];

            foreach ($lines as $product) {
                $variant = $product->variants->first();
                $price = $variant->price ?? $product->price;
                $qty = 1 + ($i % 2);
                $subtotal += $price * $qty;
                $items[] = [
                    'product_id' => $product->id, 'product_variant_id' => $variant?->id, 'name' => $product->name,
                    'variant_label' => $variant?->label(), 'image' => $product->mainImage?->path,
                    'price' => $price, 'quantity' => $qty, 'line_total' => $price * $qty,
                ];
            }

            $fee = $zone?->feeFor($subtotal) ?? 0;
            $created = now()->subDays(29 - $i * 2)->setTime(9 + $i % 10, 15 * ($i % 4));

            $order = Order::query()->create([
                'number' => Order::newNumber(), 'email' => 'customer'.($i + 1).'@example.com', 'name' => $name, 'phone' => '0803'.str_pad((string) (1000000 + $i * 7919), 7, '0'),
                'address' => (10 + $i).' Example Street', 'city' => $city, 'state' => $state, 'delivery_zone_id' => $zone?->id, 'delivery_eta' => $zone?->eta,
                'subtotal' => $subtotal, 'discount' => 0, 'delivery_fee' => $fee, 'total' => $subtotal + $fee,
                'status' => $status, 'payment_method' => $i % 3 === 0 ? 'bank_transfer' : 'paystack',
                'paid_at' => $status->isPaid() ? $created->copy()->addMinutes(5) : null,
            ]);
            $order->forceFill(['created_at' => $created, 'updated_at' => $created])->saveQuietly();
            $order->items()->createMany($items);

            $steps = $status === OrderStatus::Cancelled ? [OrderStatus::Pending, OrderStatus::Cancelled] : array_slice(OrderStatus::timeline(), 0, $status->step() + 1);
            foreach ($steps as $n => $step) {
                $order->history()->create(['status' => $step])->forceFill(['created_at' => $created->copy()->addHours($n * 20)])->saveQuietly();
            }
        }
    }
}
