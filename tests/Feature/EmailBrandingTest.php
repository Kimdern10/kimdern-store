<?php

use App\Mail\OrderStatusChanged;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;

it('brands the verify-email message with the shop name, header and contact footer', function () {
    Setting::put(['phone' => '0809 111 2222']);
    $user = User::factory()->unverified()->create(['name' => 'Ada Obi']);

    $mail = (new VerifyEmail)->toMail($user);
    $html = (string) $mail->render();

    expect($mail->subject)->toBe('Confirm your email for Kimdern Store');
    expect($html)->toContain('Welcome, Ada!')
        ->toContain('class="wordmark"')
        ->toContain('0809 111 2222');
});

it('brands the password reset message', function () {
    $user = User::factory()->create(['name' => 'Ada Obi']);

    $mail = (new ResetPassword('token'))->toMail($user);

    expect($mail->subject)->toBe('Reset your Kimdern Store password')
        ->and((string) $mail->render())->toContain('Choose a new password');
});

it('tells the customer what changed on their order', function () {
    $order = Order::factory()->create(['status' => 'shipped', 'tracking_number' => 'GIG-99']);

    (new OrderStatusChanged($order, 'Rider will call.'))
        ->assertHasSubject("Your order {$order->number} is on its way")
        ->assertSeeInHtml('GIG-99')
        ->assertSeeInHtml('Rider will call.')
        ->assertSeeInHtml('Kimdern Store');
});
