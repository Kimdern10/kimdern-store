<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_customers_are_sent_to_their_orders(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('dashboard'))->assertRedirect(route('account.orders'));
        $this->get(route('account.orders'))->assertOk();
    }

    public function test_the_shop_owner_is_sent_to_the_admin(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
    }
}
