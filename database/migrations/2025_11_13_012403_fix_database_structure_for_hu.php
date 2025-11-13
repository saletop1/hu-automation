<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Tambahkan kolom ke stock_data jika belum ada
        if (!Schema::hasColumn('stock_data', 'hu_created')) {
            Schema::table('stock_data', function (Blueprint $table) {
                $table->boolean('hu_created')->default(false)->after('last_updated');
            });
        }

        if (!Schema::hasColumn('stock_data', 'hu_created_at')) {
            Schema::table('stock_data', function (Blueprint $table) {
                $table->timestamp('hu_created_at')->nullable()->after('hu_created');
            });
        }

        if (!Schema::hasColumn('stock_data', 'hu_number')) {
            Schema::table('stock_data', function (Blueprint $table) {
                $table->string('hu_number')->nullable()->after('hu_created_at');
            });
        }

        // Buat tabel hu_history jika belum ada
        if (!Schema::hasTable('hu_history')) {
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
    }

    public function down()
    {
        // Jangan hapus kolom di stock_data karena mungkin data sudah ada
        // Jangan drop tabel hu_history karena mungkin data sudah ada
        // Migration ini aman, tidak ada rollback yang berbahaya
    }
};
