<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloads orders as a CSV file (opens in Excel or Google Sheets), using the same
 * status and search filters as the admin orders page.
 */
class OrderExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('search', ''));

        $orders = Order::query()->with('items')
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('number', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->latest();

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF"); // so Excel reads ₦ and accents correctly

            fputcsv($out, ['Order', 'Date', 'Status', 'Paid on', 'Payment', 'Customer', 'Email', 'Phone', 'Address', 'City', 'State', 'Items', 'Subtotal (₦)', 'Discount (₦)', 'Delivery (₦)', 'Total (₦)', 'Coupon']);

            $orders->chunk(200, function ($chunk) use ($out) {
                foreach ($chunk as $order) {
                    fputcsv($out, [
                        $order->number,
                        $order->created_at->format('Y-m-d H:i'),
                        $order->status->label(),
                        $order->paid_at?->format('Y-m-d H:i'),
                        $order->paymentMethodLabel(),
                        $order->name,
                        $order->email,
                        $order->phone,
                        $order->address,
                        $order->city,
                        $order->state,
                        $order->items->map(fn ($i) => $i->quantity.' x '.$i->name.($i->variant_label ? " ({$i->variant_label})" : ''))->implode('; '),
                        $order->subtotal / 100,
                        $order->discount / 100,
                        $order->delivery_fee / 100,
                        $order->total / 100,
                        $order->coupon_code,
                    ]);
                }
            });

            fclose($out);
        }, 'orders-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
