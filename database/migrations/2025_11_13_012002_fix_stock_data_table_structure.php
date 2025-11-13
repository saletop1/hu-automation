<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Hanya tambahkan kolom jika belum ada
        if (!Schema::hasColumn('stock_data', 'material_description')) {
            Schema::table('stock_data', function (Blueprint $table) {
                $table->string('material_description')->nullable()->after('material');
            });
        }

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
    }

    public function down()
    {
        // Tidak perlu rollback untuk safety
    }
};
