<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('staff_logins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id'); // links to the users table (the actual Shopify shop / data owner)
            $table->string('username')->unique();
            $table->string('password');
            $table->string('display_name')->nullable();
            $table->string('role')->default('staff'); // admin, staff, etc (for future use)
            $table->timestamps();

            $table->foreign('shop_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('staff_logins');
    }
};
