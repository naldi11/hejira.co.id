<?php

namespace App\Services;

use App\Models\JihansTransaction;
use App\Models\HendhysTransaction;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceService
{
    public function generateJihansInvoice(JihansTransaction $transaction, bool $stream = true)
    {
        $transaction->load(['details.product', 'creator', 'customer']);
        
        $pdf = Pdf::loadView('invoices.jihans', compact('transaction'))
                  ->setPaper([0, 0, 684, 396], 'portrait');

        if ($stream) {
            return $pdf->stream('INV-' . $transaction->transaction_number . '.pdf');
        }

        return $pdf->output();
    }

    /**
     * Padanan Hendhys dari generateJihansInvoice(). View-nya sudah ada
     * (resources/views/invoices/hendhys.blade.php) namun belum dipasang ke route
     * mana pun — dipertahankan agar kedua unit bisnis punya kemampuan yang setara.
     */
    public function generateHendhysInvoice(HendhysTransaction $transaction, bool $stream = true)
    {
        $transaction->load(['details.product', 'creator', 'customer', 'branch']);
        
        $pdf = Pdf::loadView('invoices.hendhys', compact('transaction'))
                  ->setPaper([0, 0, 684, 396], 'portrait');

        if ($stream) {
            return $pdf->stream('INV-' . $transaction->transaction_number . '.pdf');
        }

        return $pdf->output();
    }
}
