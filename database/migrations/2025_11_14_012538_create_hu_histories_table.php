<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('hu_histories', function (Blueprint $table) {
            $table->id();
            $table->string('hu_number');
            $table->string('material');
            $table->string('material_description')->nullable();
            $table->string('batch')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->string('unit')->default('PC');
            $table->string('sales_document')->nullable();
            $table->string('plant');
            $table->string('storage_location');
            $table->string('scenario_type');
            $table->string('created_by');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hu_histories');
    }
};
