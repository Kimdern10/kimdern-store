<?php

use App\Support\CustomerIdentity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->boolean('first_order_only')->default(false)->after('max_uses');
            $table->boolean('once_per_customer')->default(false)->after('first_order_only');
        });

        Schema::table('orders', function (Blueprint $table) {
            // Cleaned-up email and phone, so "ada.obi+1@gmail.com" and "adaobi@gmail.com" count as the same person.
            $table->string('email_key')->nullable()->after('email')->index();
            $table->string('phone_key', 20)->nullable()->after('phone')->index();
        });

        DB::table('orders')->select(['id', 'email', 'phone'])->orderBy('id')->each(function (object $order) {
            DB::table('orders')->where('id', $order->id)->update([
                'email_key' => CustomerIdentity::email($order->email),
                'phone_key' => CustomerIdentity::phone($order->phone),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['email_key']);
            $table->dropIndex(['phone_key']);
            $table->dropColumn(['email_key', 'phone_key']);
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['first_order_only', 'once_per_customer']);
        });
    }
};
