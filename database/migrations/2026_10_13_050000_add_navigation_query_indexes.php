<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEXES = [
        'production_lots' => ['nav_lots_remaining_index', ['entity', 'remaining', 'production_date']],
        'hendhys_transactions' => ['nav_hendhys_sales_index', ['status', 'date']],
        'gudang_transfer_requests' => ['nav_requests_status_index', ['status']],
        'gudang_transfer_out' => ['nav_transfers_pending_index', ['to_entity', 'status', 'branch_id']],
    ];

    public function up(): void
    {
        // MySQL DDL commits per statement; allow retry after an interrupted run.
        foreach (self::INDEXES as $table => [$name, $columns]) {
            if (Schema::hasTable($table) && !Schema::hasIndex($table, $name)) {
                Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => [$name]) {
            if (Schema::hasTable($table) && Schema::hasIndex($table, $name)) {
                Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
            }
        }
    }
};
