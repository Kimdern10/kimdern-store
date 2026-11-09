<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the shop. Demo orders are only added on a local copy so the admin dashboard has something to show.
     */
    public function run(): void
    {
        $this->call(ShopSeeder::class);

        if (app()->isLocal()) {
            $this->call(DemoOrderSeeder::class);
        }
    }
}
