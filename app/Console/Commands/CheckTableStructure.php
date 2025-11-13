<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckTableStructure extends Command
{
    protected $signature = 'table:check';
    protected $description = 'Check table structure';

    public function handle()
    {
        $tables = ['stock_data', 'hu_history'];

        foreach ($tables as $table) {
            $this->info("Checking table: {$table}");

            if (!Schema::hasTable($table)) {
                $this->error("Table {$table} does not exist");
                continue;
            }

            $columns = DB::getSchemaBuilder()->getColumnListing($table);
            $this->info("Columns in {$table}: " . implode(', ', $columns));
        }

        return Command::SUCCESS;
    }
}
