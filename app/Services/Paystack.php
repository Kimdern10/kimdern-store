<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Talks to Paystack: start a payment, check it, and check webhook signatures.
 * Amounts go to Paystack in kobo, which is how the shop already stores money.
 */
class Paystack
{
    /**
     * Paystack is used only when the secret key is set; otherwise the shop takes bank transfers.
     */
    public function isConfigured(): bool
    {
        return filled(config('services.paystack.secret_key'));
    }

    /**
     * How the customer can pay on Paystack's page. "transfer" shows a one-time account number the
     * customer sends money to from any bank app (OPay, Moniepoint, Kuda, GTBank...); Paystack sees the
     * money arrive and confirms the payment by itself within seconds.
     *
     * @var array<string, list<string>>
     */
    public const array CHANNELS = [
        'transfer' => ['bank_transfer'],
        'card' => ['card', 'ussd', 'bank'],
    ];

    /**
     * Start a payment for the order and return the Paystack page to send the customer to.
     *
     * @param  'transfer'|'card'|null  $channel
     */
    public function initialize(Order $order, string $callbackUrl, ?string $channel = null): string
    {
        $reference = $order->number.'-'.Str::upper(Str::random(6));

        $response = $this->client()->post('/transaction/initialize', [
            'email' => $order->email,
            'amount' => $order->total,
            'currency' => 'NGN',
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => ['order_number' => $order->number, 'customer_name' => $order->name],
        ] + (isset(self::CHANNELS[$channel ?? '']) ? ['channels' => self::CHANNELS[$channel]] : []));

        $url = $response->json('data.authorization_url');

        if (! $response->successful() || ! $response->json('status') || ! is_string($url)) {
            throw new RuntimeException('Paystack could not start the payment: '.$response->json('message', 'no response'));
        }

        $order->forceFill(['payment_reference' => $reference, 'payment_channel' => $channel])->save();

        return $url;
    }

    /**
     * Ask Paystack what happened to a payment.
     *
     * @return array<string, mixed>|null The transaction, or null if Paystack doesn't know it.
     */
    public function verify(string $reference): ?array
    {
        $response = $this->client()->get('/transaction/verify/'.rawurlencode($reference));
        $data = $response->json('data');

        return $response->successful() && $response->json('status') && is_array($data) ? $data : null;
    }

    /**
     * Check that a webhook really came from Paystack.
     */
    public function validSignature(string $payload, ?string $signature): bool
    {
        return $this->isConfigured() && is_string($signature) && hash_equals($this->sign($payload), $signature);
    }

    /**
     * The signature Paystack puts in the x-paystack-signature header.
     */
    public function sign(string $payload): string
    {
        return hash_hmac('sha512', $payload, (string) config('services.paystack.secret_key'));
    }

    private function client(): PendingRequest
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Paystack keys are not set. Add PAYSTACK_SECRET_KEY to the .env file.');
        }

        return Http::baseUrl((string) config('services.paystack.base_url'))
            ->withToken((string) config('services.paystack.secret_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->retry(2, 500, throw: false);
    }
}
