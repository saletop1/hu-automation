<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stock_data', function (Blueprint $table) {
            $table->string('material_description', 255)->nullable()->after('material');
        });
    }

    public function down()
    {
        Schema::table('stock_data', function (Blueprint $table) {
            $table->dropColumn('material_description');
        });
    }
};
