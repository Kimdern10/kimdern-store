<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Attachment;

/**
 * The PDF receipt for an order: downloaded from the order page and attached to emails.
 */
class Receipt
{
    public static function pdf(Order $order): string
    {
        $order->loadMissing('items');

        return Pdf::loadView('pdf.receipt', [
            'order' => $order,
            'store' => Setting::values(),
            'logo' => self::logoDataUri(),
        ])->setPaper('a4')->output();
    }

    public static function filename(Order $order): string
    {
        return 'receipt-'.$order->number.'.pdf';
    }

    public static function attachment(Order $order): Attachment
    {
        return Attachment::fromData(fn () => self::pdf($order), self::filename($order))->withMime('application/pdf');
    }

    /**
     * The uploaded logo inlined (dompdf can't fetch images from the web here), or null to use the text logo.
     */
    private static function logoDataUri(): ?string
    {
        $path = Setting::get('logo');

        if (blank($path) || ! is_file($file = storage_path('app/public/'.$path))) {
            return null;
        }

        return 'data:image/webp;base64,'.base64_encode((string) file_get_contents($file));
    }
}
