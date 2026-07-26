<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('shopify_variant_id')->nullable()->index();
            $table->string('title')->nullable(); // e.g. "Small / Red"
            $table->string('sku')->nullable()->index();
            $table->string('barcode')->nullable()->index();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->integer('inventory_quantity')->default(0);
            $table->decimal('weight', 10, 2)->nullable();
            $table->string('weight_unit')->nullable(); // kg, g, lb, oz
            $table->string('option1')->nullable(); // e.g. Size
            $table->string('option2')->nullable(); // e.g. Color
            $table->string('option3')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_variants');
    }
};