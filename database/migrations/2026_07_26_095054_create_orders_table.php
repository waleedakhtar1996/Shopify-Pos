<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('shopify_order_id')->index();
            $table->string('order_number')->nullable();
            $table->string('financial_status')->nullable();   // paid, pending, refunded, etc
            $table->string('fulfillment_status')->nullable();  // fulfilled, unfulfilled, partial
            $table->string('payment_method')->nullable();      // gateway name
            $table->decimal('subtotal_price', 12, 2)->default(0);
            $table->decimal('total_tax', 12, 2)->default(0);
            $table->decimal('total_discounts', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->string('currency', 10)->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->text('shipping_address')->nullable();
            $table->timestamp('shopify_created_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->unique(['user_id', 'shopify_order_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
