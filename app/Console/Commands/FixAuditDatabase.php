<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixAuditDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-audit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Full audit and fix of all timezone issues in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai Audit dan Perbaikan Penuh Database...');

        DB::transaction(function () {
            // 1. Pulihkan hendhys_transactions dari updated_at (sudah dilakukan user, tapi kita pastikan ulang)
            $this->info('1. Memulihkan hendhys_transactions dari updated_at...');
            DB::statement("
                UPDATE hendhys_transactions 
                SET created_at = updated_at,
                    date = DATE(updated_at),
                    time = TIME(updated_at)
                WHERE updated_at IS NOT NULL
            ");

            // 2. Pulihkan jihans_transactions dari updated_at
            $this->info('2. Memulihkan jihans_transactions dari updated_at...');
            DB::statement("
                UPDATE jihans_transactions 
                SET created_at = updated_at,
                    date = DATE(updated_at),
                    time = TIME(updated_at)
                WHERE updated_at IS NOT NULL
            ");

            // 3. Pulihkan hendhys_transaction_payments
            $this->info('3. Menyelaraskan hendhys_transaction_payments dengan transaksi induk...');
            // Karena hendhys_transaction_payments punya updated_at, kita bisa restore langsung.
            DB::statement("
                UPDATE hendhys_transaction_payments 
                SET created_at = updated_at
                WHERE updated_at IS NOT NULL
            ");

            // 4. Pulihkan jihans_transaction_payments
            $this->info('4. Menyelaraskan jihans_transaction_payments dengan transaksi induk...');
            // Karena tidak ada updated_at, kita ambil dari transaksi induknya
            DB::statement("
                UPDATE jihans_transaction_payments p
                JOIN jihans_transactions t ON p.transaction_id = t.id
                SET p.created_at = t.created_at
            ");

            // 5. Perbaiki master_cashier_shifts yang jam bukanya terbalik (lebih dari jam tutup)
            $this->info('5. Memperbaiki Laci Kasir (Shift) yang terinversi waktunya...');
            DB::statement("
                UPDATE master_cashier_shifts 
                SET opened_at = DATE_SUB(opened_at, INTERVAL 7 HOUR)
                WHERE opened_at > closed_at AND closed_at IS NOT NULL
            ");
            
            // Perbaiki juga jika ada shift yang masih open tapi jam bukanya masa depan
            $sekarang = now()->toDateTimeString();
            DB::statement("
                UPDATE master_cashier_shifts 
                SET opened_at = DATE_SUB(opened_at, INTERVAL 7 HOUR)
                WHERE opened_at > '$sekarang' AND status = 'open'
            ");

            $this->info('Semua data berhasil diselaraskan dan dinormalkan!');
        });

        $this->info('Proses selesai.');
    }
}
