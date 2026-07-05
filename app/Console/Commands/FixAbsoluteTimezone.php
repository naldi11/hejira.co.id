<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixAbsoluteTimezone extends Command
{
    protected $signature = 'app:fix-absolute';
    protected $description = 'Menyelaraskan jam transaksi secara absolut dengan tabel pembayaran';

    public function handle()
    {
        $this->info('Memulai sinkronisasi absolut berdasarkan tabel payments...');

        $this->info('Menyinkronkan jam hendhys_transactions...');
        DB::statement("UPDATE hendhys_transactions t
            JOIN hendhys_transaction_payments p ON t.id = p.transaction_id
            SET t.created_at = p.created_at,
                t.updated_at = p.updated_at,
                t.date = DATE(p.created_at),
                t.time = TIME(p.created_at)");

        $this->info('Menyinkronkan jam jihans_transactions...');
        DB::statement("UPDATE jihans_transactions t
            JOIN jihans_transaction_payments p ON t.id = p.transaction_id
            SET t.created_at = p.created_at,
                t.date = DATE(p.created_at),
                t.time = TIME(p.created_at)");

        $this->info('Memperbaiki shift yang terbalik (fallback pengamanan)...');
        DB::statement("UPDATE master_cashier_shifts 
            SET opened_at = DATE_SUB(opened_at, INTERVAL 24 HOUR)
            WHERE status = 'closed' AND opened_at > closed_at");

        $this->info('Sinkronisasi absolut selesai! Seluruh transaksi kasir sudah dikunci ke jam aslinya.');
    }
}
