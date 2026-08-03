<?php

namespace App\Exceptions;

use App\Models\Product;
use RuntimeException;

/**
 * Dilempar ketika sebuah operasi mencoba memotong stok melebihi saldo yang tersedia.
 *
 * Sebelumnya StockService diam-diam meng-clamp hasil ke 0 (max(0, $before - $qty)),
 * sehingga penjualan/transfer tetap "berhasil" walau stok fisik tidak mencukupi dan
 * baris kartu stok menjadi tidak konsisten (quantity_before - quantity_after != quantity).
 * Dengan exception ini, transaksi yang membungkusnya akan di-rollback.
 */
class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly int $productId,
        public readonly int $requested,
        public readonly int $available,
        public readonly string $stockLabel = 'stok'
    ) {
        parent::__construct($this->buildMessage());
    }

    private function buildMessage(): string
    {
        $name = Product::where('id', $this->productId)->value('name') ?? "ID {$this->productId}";

        // Kuantitas selalu bilangan bulat — tampilkan tanpa desimal.
        return sprintf(
            'Stok tidak mencukupi untuk produk %s. Diminta %s, tersedia %s pada %s.',
            $name,
            number_format($this->requested, 0, ',', '.'),
            number_format($this->available, 0, ',', '.'),
            $this->stockLabel
        );
    }

    /**
     * Kegagalan stok adalah kesalahan input pengguna (422), bukan error server (500).
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error'   => $this->getMessage(),
                'message' => $this->getMessage(),
            ], 422);
        }

        return back()->withErrors(['stock' => $this->getMessage()]);
    }
}
