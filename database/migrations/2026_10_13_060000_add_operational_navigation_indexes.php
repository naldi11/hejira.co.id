<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEXES = [
        'jihans_transactions' => ['nav_jihans_cashier_time', ['created_by', 'created_at']],
        'hendhys_transactions' => ['nav_hendhys_cashier_time', ['created_by', 'created_at']],
        'master_cashier_shifts' => ['nav_shift_previous', ['user_id', 'branch_id', 'id', 'closed_at']],
        'hendhys_stock_movements' => ['nav_branch_movements_time', ['branch_id', 'created_at', 'id']],
        'stock_opname_counts' => ['nav_opname_movement', ['stock_movement_table', 'stock_movement_id']],
        'jihans_transaction_payments' => ['nav_jihans_payment_transaction', ['transaction_id']],
        'hendhys_transaction_payments' => ['nav_hendhys_payment_transaction', ['transaction_id']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => [$name, $columns]) {
            // MySQL usually already indexes foreign keys; SQLite does not.
            // Reuse any index with the same leading columns instead of duplicating it.
            if (Schema::hasTable($table) && !Schema::hasIndex($table, $name)
                && !collect(Schema::getIndexes($table))->contains(fn ($index) => array_slice($index['columns'], 0, count($columns)) === $columns)) {
                Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => [$name]) {
            if (Schema::hasTable($table) && Schema::hasIndex($table, $name)) Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
        }
    }
};
