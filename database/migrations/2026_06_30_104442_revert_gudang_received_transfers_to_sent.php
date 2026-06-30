<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fetch all TransferOut to hendhys with received status on 2026-06-30
        $transfers = DB::table('gudang_transfer_out')
            ->where('to_entity', 'hendhys')
            ->where('status', 'received')
            ->where('date', '2026-06-30')
            ->get();

        foreach ($transfers as $t) {
            $details = DB::table('gudang_transfer_out_details')
                ->where('transfer_id', $t->id)
                ->get();

            // 1. Revert branch stock
            foreach ($details as $d) {
                $qty = (float) $d->received_quantity;
                if ($qty > 0) {
                    DB::table('hendhys_stock_branch')
                        ->where('branch_id', $t->branch_id)
                        ->where('product_id', $d->product_id)
                        ->decrement('quantity', $qty);
                }
            }

            // 2. Delete BAST (ReceiptConfirmation) and details
            $bastIds = DB::table('receipt_confirmations')
                ->where('receiptable_type', 'App\Models\TransferOut')
                ->where('receiptable_id', $t->id)
                ->pluck('id');

            if ($bastIds->isNotEmpty()) {
                DB::table('receipt_confirmation_details')
                    ->whereIn('receipt_confirmation_id', $bastIds)
                    ->delete();

                DB::table('receipt_confirmations')
                    ->whereIn('id', $bastIds)
                    ->delete();
            }

            // 3. Delete Stock In (hendhys_stock_in) and movements
            DB::table('hendhys_stock_in')
                ->where('transfer_out_id', $t->id)
                ->delete();

            DB::table('hendhys_stock_movements')
                ->where('branch_id', $t->branch_id)
                ->where('reference_id', $t->id)
                ->where('source', 'transfer_gudang')
                ->delete();

            // 4. Update details received_quantity & kondisi back to null
            DB::table('gudang_transfer_out_details')
                ->where('transfer_id', $t->id)
                ->update([
                    'received_quantity' => null,
                    'kondisi' => null,
                ]);

            // 5. Update transfer status to sent
            DB::table('gudang_transfer_out')
                ->where('id', $t->id)
                ->update([
                    'status' => 'sent',
                    'received_by' => null,
                    'receive_notes' => null,
                    'receive_photo' => null,
                    'receive_kendala' => null,
                    'receive_received_by_name' => null,
                    'receive_pengirim_name' => null,
                    'received_at' => null,
                ]);
        }
    }

    public function down(): void
    {
        // No down needed as this is a one-way correction.
    }
};
