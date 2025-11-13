<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stock_data', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_data', 'hu_created')) {
                $table->boolean('hu_created')->default(false)->after('last_updated');
            }
            if (!Schema::hasColumn('stock_data', 'hu_created_at')) {
                $table->timestamp('hu_created_at')->nullable()->after('hu_created');
            }
            if (!Schema::hasColumn('stock_data', 'hu_number')) {
                $table->string('hu_number')->nullable()->after('hu_created_at');
            }
        });
    }

    public function down()
    {
        Schema::table('stock_data', function (Blueprint $table) {
            $table->dropColumn(['hu_created', 'hu_created_at', 'hu_number']);
        });
    }
};
