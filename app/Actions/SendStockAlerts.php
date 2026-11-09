<?php

namespace App\Actions;

use App\Mail\BackInStock;
use App\Models\Product;
use App\Models\StockAlert;
use App\Support\Mailer;

/**
 * Emails everyone waiting for a product (or one of its sizes/colours) that is in stock again.
 */
class SendStockAlerts
{
    /**
     * @return int How many emails were sent.
     */
    public function handle(Product $product): int
    {
        if (! $product->is_active) {
            return 0;
        }

        $product->load('variants');
        $sent = 0;

        $alerts = StockAlert::query()->with(['product', 'variant'])
            ->where('product_id', $product->id)
            ->whereNull('notified_at')
            ->get();

        foreach ($alerts as $alert) {
            $available = $alert->variant
                ? $alert->variant->stock > 0
                : $product->totalStock() > 0;

            if (! $available) {
                continue;
            }

            Mailer::send($alert->email, new BackInStock($alert));
            $alert->forceFill(['notified_at' => now()])->save();
            $sent++;
        }

        return $sent;
    }
}
