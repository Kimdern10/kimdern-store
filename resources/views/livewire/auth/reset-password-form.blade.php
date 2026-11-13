<?php

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Choose a new password from the emailed link. Uses Fortify's ResetUserPassword rules.
 */
new class extends Component {
    #[Locked]
    public string $token = '';

    public ?string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token, ?string $email = null): void
    {
        $this->token = $token;
        $this->email = (string) $email;
    }

    public function resetPassword(ResetsUserPasswords $resetter): void
    {
        $this->validate(['email' => ['required', 'email'], 'password' => ['required']]);

        $status = Password::broker()->reset(
            ['email' => strtolower(trim((string) $this->email)), 'password' => $this->password, 'password_confirmation' => $this->password_confirmation, 'token' => $this->token],
            function (User $user) use ($resetter): void {
                $resetter->reset($user, ['password' => $this->password, 'password_confirmation' => $this->password_confirmation]);
                $user->forceFill(['remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        session()->flash('status', 'Your password has been changed. Log in with your new password.');
        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<form wire:submit="resetPassword" class="flex flex-col gap-5">
    <flux:input wire:model="email" name="email" label="Email address" type="email" required autocomplete="email" icon="envelope" />

    <flux:input
        wire:model="password"
        name="password"
        label="New password"
        type="password"
        required
        autofocus
        autocomplete="new-password"
        icon="lock-closed"
        placeholder="Create a new password"
        passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
        viewable
    />

    <x-password-strength for="password" />

    <flux:input wire:model="password_confirmation" name="password_confirmation" label="Confirm new password" type="password" required autocomplete="new-password" icon="lock-closed" placeholder="Type it again" viewable />

    <flux:button type="submit" variant="primary" class="h-12 w-full" data-test="reset-password-button">
        <span wire:loading.remove wire:target="resetPassword">Save new password</span>
        <span wire:loading wire:target="resetPassword">Saving…</span>
    </flux:button>
</form>
