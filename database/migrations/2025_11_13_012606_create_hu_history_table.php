<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('hu_history', function (Blueprint $table) {
            $table->id();
            $table->string('hu_number');
            $table->string('material');
            $table->string('material_description')->nullable();
            $table->string('batch')->nullable();
            $table->decimal('quantity', 15, 2);
            $table->string('unit');
            $table->string('plant');
            $table->string('storage_location');
            $table->string('sales_document')->nullable();
            $table->enum('scenario_type', ['single', 'single-multi', 'multiple']);
            $table->string('created_by');
            $table->timestamps();

            $table->index(['hu_number', 'material']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hu_history');
    }
};
