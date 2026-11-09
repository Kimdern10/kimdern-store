<?php

namespace App\Console\Commands;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('app:create-admin {--name= : The owner\'s name} {--email= : The email used to log in} {--password= : The password (you will be asked if left out)}')]
#[Description('Create the shop owner\'s admin login, or turn an existing account into one')]
class CreateAdmin extends Command
{
    use PasswordValidationRules;

    public function handle(): int
    {
        $email = strtolower((string) ($this->option('email') ?: text('Email address to log in with', required: true)));
        $user = User::query()->where('email', $email)->first();

        $name = $this->option('name') ?: ($user->name ?? text('Your name', default: 'Shop Owner', required: true));
        $password = $this->option('password') ?: password('Password (at least 8 characters)', required: true);

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $password],
            ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255'], 'password' => $this->passwordRules()],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user ??= new User;

        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_admin' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $this->components->info("{$user->email} can now log in to the admin at ".route('login'));

        return self::SUCCESS;
    }
}
