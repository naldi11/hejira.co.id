<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UndoDoubleTimezone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:undo-double-timezone';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Undo the double +7 hours timezone update for shifts and transactions.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai undo timezone ganda...');

        // 1. master_cashier_shifts
        // SUDAH BERHASIL pada run pertama, jadi kita comment agar tidak berulang (tidak mundur lagi).
        $this->info('Tabel master_cashier_shifts sudah berhasil di-undo sebelumnya (Dilewati).');

        // 2. jihans_transactions
        // Sangat aman: sinkronisasi langsung dengan tabel pembayaran yang tidak pernah tersentuh
        // Menggunakan p.created_at untuk semua karena p.updated_at tidak ada di tabel payment
        $this->info('Memperbaiki tabel jihans_transactions...');
        DB::statement("UPDATE jihans_transactions t 
            JOIN jihans_transaction_payments p ON t.id = p.transaction_id
            SET t.created_at = DATE_ADD(p.created_at, INTERVAL 7 HOUR),
                t.updated_at = DATE_ADD(p.created_at, INTERVAL 7 HOUR),
                t.date = DATE(DATE_ADD(p.created_at, INTERVAL 7 HOUR)),
                t.time = TIME(DATE_ADD(p.created_at, INTERVAL 7 HOUR))");

        // 3. hendhys_transactions
        // Sangat aman: sinkronisasi langsung dengan tabel pembayaran
        $this->info('Memperbaiki tabel hendhys_transactions...');
        DB::statement("UPDATE hendhys_transactions t 
            JOIN hendhys_transaction_payments p ON t.id = p.transaction_id
            SET t.created_at = DATE_ADD(p.created_at, INTERVAL 7 HOUR),
                t.updated_at = DATE_ADD(p.created_at, INTERVAL 7 HOUR),
                t.date = DATE(DATE_ADD(p.created_at, INTERVAL 7 HOUR)),
                t.time = TIME(DATE_ADD(p.created_at, INTERVAL 7 HOUR))");

        $this->info('Perbaikan selesai! Waktu double-update berhasil dinormalkan.');
    }
}
