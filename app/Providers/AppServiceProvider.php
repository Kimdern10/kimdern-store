<?php

namespace App\Providers;

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Models\Setting;
use App\Models\User;
use App\Support\Cart;
use App\Support\Seo;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(Cart::class, fn ($app) => new Cart($app['session.store']));
        $this->app->scoped(Seo::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->brandAccountEmails();

        // Re-check admin access on every Livewire button click, not just when the page first loads.
        Livewire::addPersistentMiddleware([EnsureUserIsAdmin::class]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(8)
                ->letters()
                ->numbers()
                ->uncompromised()
            : null,
        );
    }

    /**
     * The "verify your email" and "reset your password" emails, in the shop's words and branding.
     */
    protected function brandAccountEmails(): void
    {
        VerifyEmail::toMailUsing(fn (User $user, string $url): MailMessage => (new MailMessage)
            ->subject('Confirm your email for '.Setting::get('store_name'))
            ->greeting('Welcome, '.Str::of($user->name)->before(' ').'!')
            ->line('Thank you for creating an account with '.Setting::get('store_name').'. Please confirm this is your email address so we can send you order updates.')
            ->action('Confirm my email', $url)
            ->line('The link works for '.config('auth.verification.expire', 60).' minutes. If you did not create an account, you can ignore this email.')
            ->salutation('With love, '.Setting::get('store_name')));

        ResetPassword::toMailUsing(fn (User $user, string $token): MailMessage => (new MailMessage)
            ->subject('Reset your '.Setting::get('store_name').' password')
            ->greeting('Hello '.Str::of($user->name)->before(' ').',')
            ->line('Someone (hopefully you) asked to reset the password for your '.Setting::get('store_name').' account.')
            ->action('Choose a new password', url(route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()], false)))
            ->line('The link works for '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes.')
            ->line('If you did not ask for this, ignore this email and your password will stay the same.')
            ->salutation('With love, '.Setting::get('store_name')));
    }
}
