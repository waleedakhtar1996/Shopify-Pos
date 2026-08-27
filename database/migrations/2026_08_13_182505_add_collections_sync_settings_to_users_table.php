<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('collections_sync_frequency')->default(5)->after('returns_sync_frequency');
            $table->timestamp('collections_last_synced_at')->nullable()->after('returns_last_synced_at');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['collections_sync_frequency', 'collections_last_synced_at']);
        });
    }
};
