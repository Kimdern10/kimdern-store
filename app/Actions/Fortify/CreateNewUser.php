<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9 ]{10,16}$/'],
            'password' => $this->passwordRules(),
        ], [
            'phone.regex' => 'Enter a valid phone number, e.g. 0803 123 4567.',
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => strtolower($input['email']),
            'phone' => filled($input['phone'] ?? null) ? $input['phone'] : null,
            'password' => $input['password'],
        ]);
    }
}
