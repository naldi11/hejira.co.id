<?php

namespace App\Services;

use App\Models\JihansTransaction;
use App\Models\HendhysTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function generateJihansInvoice(JihansTransaction $transaction, bool $stream = true)
    {
        $transaction->load(['details.product', 'creator', 'customer']);
        
        $pdf = Pdf::loadView('invoices.jihans', compact('transaction'))
                  ->setPaper('a4', 'portrait');

        if ($stream) {
            return $pdf->stream('INV-' . $transaction->transaction_number . '.pdf');
        }

        return $pdf->output();
    }

    public function generateHendhysInvoice(HendhysTransaction $transaction, bool $stream = true)
    {
        $transaction->load(['details.product', 'creator', 'customer', 'branch']);
        
        $pdf = Pdf::loadView('invoices.hendhys', compact('transaction'))
                  ->setPaper('a4', 'portrait');

        if ($stream) {
            return $pdf->stream('INV-' . $transaction->transaction_number . '.pdf');
        }

        return $pdf->output();
    }

    public function saveInvoiceToStorage($pdfOutput, string $filename)
    {
        Storage::put('invoices/' . $filename, $pdfOutput);
        return 'invoices/' . $filename;
    }
}
