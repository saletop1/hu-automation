<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stocks', function (Blueprint $table) {
            if (!Schema::hasColumn('stocks', 'hu_created')) {
                $table->boolean('hu_created')->default(false)->after('last_updated');
            }
            if (!Schema::hasColumn('stocks', 'hu_created_at')) {
                $table->timestamp('hu_created_at')->nullable()->after('hu_created');
            }
            if (!Schema::hasColumn('stocks', 'hu_number')) {
                $table->string('hu_number')->nullable()->after('hu_created_at');
            }
        });
    }

    public function down()
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['hu_created', 'hu_created_at', 'hu_number']);
        });
    }
};
