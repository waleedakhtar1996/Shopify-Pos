<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('handle');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->boolean('track_quantity')->default(true)->after('meta_description');
            $table->boolean('continue_selling_when_out_of_stock')->default(false)->after('track_quantity');
            $table->boolean('is_physical_product')->default(true)->after('continue_selling_when_out_of_stock');
            $table->string('collection')->nullable()->after('is_physical_product');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'meta_title',
                'meta_description',
                'track_quantity',
                'continue_selling_when_out_of_stock',
                'is_physical_product',
                'collection',
            ]);
        });
    }
};
