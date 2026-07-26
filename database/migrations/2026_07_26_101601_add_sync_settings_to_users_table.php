<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('orders_sync_frequency')->default(5)->after('password');   // minutes
            $table->integer('customers_sync_frequency')->default(5)->after('orders_sync_frequency');
            $table->integer('products_sync_frequency')->default(5)->after('customers_sync_frequency');
            $table->timestamp('orders_last_synced_at')->nullable()->after('products_sync_frequency');
            $table->timestamp('customers_last_synced_at')->nullable()->after('orders_last_synced_at');
            $table->timestamp('products_last_synced_at')->nullable()->after('customers_last_synced_at');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'orders_sync_frequency',
                'customers_sync_frequency',
                'products_sync_frequency',
                'orders_last_synced_at',
                'customers_last_synced_at',
                'products_last_synced_at',
            ]);
        });
    }
};
