<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checks on every request (page load and every button click) that the user is the shop owner,
 * on top of the "admin" route middleware.
 */
trait AdminOnly
{
    public function bootAdminOnly(): void
    {
        abort_unless(Auth::user()?->is_admin, Response::HTTP_FORBIDDEN);
    }
}
