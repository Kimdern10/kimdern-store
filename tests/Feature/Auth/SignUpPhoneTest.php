<?php

use App\Models\User;

it('saves the optional phone number when someone signs up', function () {
    $this->post(route('register.store'), [
        'name' => 'Adaeze Okafor',
        'email' => 'Adaeze@Example.com',
        'phone' => '0803 123 4567',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasNoErrors();

    $user = User::query()->sole();
    expect($user->email)->toBe('adaeze@example.com')
        ->and($user->phone)->toBe('0803 123 4567');
});

it('lets people sign up without a phone number but rejects a bad one', function () {
    $this->post(route('register.store'), [
        'name' => 'Bola', 'email' => 'bola@example.com', 'phone' => 'call me',
        'password' => 'password123', 'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('phone');

    $this->post(route('register.store'), [
        'name' => 'Bola', 'email' => 'bola@example.com',
        'password' => 'password123', 'password_confirmation' => 'password123',
    ])->assertSessionHasNoErrors();

    expect(User::query()->sole()->phone)->toBeNull();
});

it('shows the redesigned log in, sign up and password pages', function (string $route, string $text) {
    $this->get(route($route, $route === 'password.reset' ? ['token' => 'abc'] : []))->assertOk()->assertSee($text, false);
})->with([
    ['login', 'Welcome back'],
    ['register', 'Create your account'],
    ['password.request', 'Forgot your password?'],
    ['password.reset', 'Choose a new password'],
]);
