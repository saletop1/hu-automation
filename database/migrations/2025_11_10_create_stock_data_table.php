<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_data', function (Blueprint $table) {
            $table->id();
            $table->string('material', 50);
            $table->string('material_description', 255)->nullable();
            $table->string('plant', 10);
            $table->string('storage_location', 10);
            $table->string('batch', 50)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->string('base_unit', 10)->nullable();
            $table->string('sales_document', 50)->nullable();
            $table->string('item_number', 50)->nullable();
            $table->string('vendor_name', 255)->nullable();
            $table->timestamp('last_updated');
            $table->timestamps();

            $table->unique(['material', 'plant', 'storage_location', 'batch'], 'unique_stock');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_data');
    }
};
