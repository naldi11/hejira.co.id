<?php

namespace App\Services;

use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    /** Serialize each cashier's checkouts and replay successful requests safely. */
    public function run(string $modelClass, array $data, Closure $create): Model
    {
        return DB::transaction(function () use ($modelClass, $data, $create) {
            User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();

            $key = $data['checkout_key'] ?? null;
            $fingerprint = hash('sha256', json_encode([$data, auth()->user()->branch_id, auth()->user()->entity], JSON_THROW_ON_ERROR));

            if ($key) {
                $existing = $modelClass::where('created_by', auth()->id())
                    ->where('checkout_key', $key)->lockForUpdate()->first();

                if ($existing) {
                    if (!hash_equals($existing->checkout_fingerprint, $fingerprint)) {
                        throw ValidationException::withMessages([
                            'checkout_key' => 'Permintaan checkout ini sudah dipakai untuk transaksi berbeda.',
                        ]);
                    }

                    return $existing;
                }
            }

            $transaction = $create();

            if ($key) {
                $transaction->forceFill([
                    'checkout_key' => $key,
                    'checkout_fingerprint' => $fingerprint,
                ])->save();
            }

            return $transaction;
        });
    }
}
