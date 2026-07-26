<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('option1_name')->nullable()->after('collection'); // e.g. "Size"
            $table->string('option2_name')->nullable()->after('option1_name'); // e.g. "Color"
            $table->string('option3_name')->nullable()->after('option2_name'); // e.g. "Material"
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['option1_name', 'option2_name', 'option3_name']);
        });
    }
};
