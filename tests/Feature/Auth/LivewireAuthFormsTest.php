<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

describe('log in form', function () {
    it('logs a customer in and sends them on', function () {
        $user = User::factory()->create(['email' => 'ada@example.com']);

        Livewire::test('auth.login-form')
            ->set('email', 'ADA@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    });

    it('sends the shop owner straight to the admin', function () {
        User::factory()->admin()->create(['email' => 'owner@example.com']);

        Livewire::test('auth.login-form')->set('email', 'owner@example.com')->set('password', 'password')->call('login')
            ->assertRedirect(route('admin.dashboard'));
    });

    it('shows an error for a wrong password without logging in', function () {
        User::factory()->create(['email' => 'ada@example.com']);

        Livewire::test('auth.login-form')->set('email', 'ada@example.com')->set('password', 'wrong-one')->call('login')
            ->assertHasErrors('email')
            ->assertSet('password', '');

        $this->assertGuest();
    });

    it('locks out after five wrong tries', function () {
        User::factory()->create(['email' => 'ada@example.com']);

        foreach (range(1, 5) as $try) {
            Livewire::test('auth.login-form')->set('email', 'ada@example.com')->set('password', 'wrong')->call('login');
        }

        Livewire::test('auth.login-form')->set('email', 'ada@example.com')->set('password', 'password')->call('login')
            ->assertHasErrors('email');
        $this->assertGuest();
    });

    it('asks for the two-step code when the account uses it', function () {
        $user = User::factory()->withTwoFactor()->create(['email' => 'ada@example.com']);

        Livewire::test('auth.login-form')->set('email', 'ada@example.com')->set('password', 'password')->call('login')
            ->assertRedirect(route('two-factor.login'));

        $this->assertGuest();
        expect(session('login.id'))->toBe($user->id);
    });
});

describe('sign up form', function () {
    it('creates the account, logs in and asks to confirm the email', function () {
        Livewire::test('auth.register-form')
            ->set('name', 'Adaeze Okafor')
            ->set('email', 'adaeze@example.com')
            ->set('phone', '0803 123 4567')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user = User::query()->sole();
        expect($user->phone)->toBe('0803 123 4567');
        $this->assertAuthenticatedAs($user);
    });

    it('shows mistakes in place', function () {
        User::factory()->create(['email' => 'taken@example.com']);

        Livewire::test('auth.register-form')
            ->set('name', '')
            ->set('email', 'taken@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'different')
            ->call('register')
            ->assertHasErrors(['name', 'email', 'password']);

        $this->assertGuest();
    });
});

describe('password reset forms', function () {
    it('emails a reset link and shows "check your email"', function () {
        Notification::fake();
        $user = User::factory()->create(['email' => 'ada@example.com']);

        Livewire::test('auth.forgot-password-form')->set('email', 'ada@example.com')->call('sendLink')
            ->assertSet('sent', true)
            ->assertSee('Check your email');

        Notification::assertSentTo($user, ResetPassword::class);
    });

    it('sets a new password from the emailed link', function () {
        $user = User::factory()->create(['email' => 'ada@example.com']);
        $token = Password::broker()->createToken($user);

        Livewire::test('auth.reset-password-form', ['token' => $token, 'email' => 'ada@example.com'])
            ->set('password', 'brand-new-pass-1')
            ->set('password_confirmation', 'brand-new-pass-1')
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertRedirect(route('login'));

        expect(Hash::check('brand-new-pass-1', $user->fresh()->password))->toBeTrue();
    });

    it('refuses a bad reset link', function () {
        User::factory()->create(['email' => 'ada@example.com']);

        Livewire::test('auth.reset-password-form', ['token' => 'not-a-real-token', 'email' => 'ada@example.com'])
            ->set('password', 'brand-new-pass-1')
            ->set('password_confirmation', 'brand-new-pass-1')
            ->call('resetPassword')
            ->assertHasErrors('email');
    });
});
