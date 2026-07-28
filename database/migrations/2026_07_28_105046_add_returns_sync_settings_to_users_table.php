<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('returns_sync_frequency')->default(5)->after('products_sync_frequency');
            $table->timestamp('returns_last_synced_at')->nullable()->after('products_last_synced_at');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['returns_sync_frequency', 'returns_last_synced_at']);
        });
    }
};
