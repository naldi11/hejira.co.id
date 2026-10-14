<?php

namespace App\Services\Izzi;

use Illuminate\Validation\ValidationException;

/** Decimal quantities use integer thousandths, never floating point stock arithmetic. */
class Quantity
{
    public const SCALE = 1000;
    public const MAX = 1000000000000;

    public static function parse(mixed $value, string $field = 'quantity', bool $allowZero = false): int
    {
        if (!preg_match('/^(\d{1,9})(?:\.(\d{1,3}))?$/', (string) $value, $match)) {
            throw ValidationException::withMessages([$field => 'Isi jumlah positif dengan maksimal 3 angka desimal.']);
        }
        $quantity = (int) $match[1] * self::SCALE + (int) str_pad($match[2] ?? '', 3, '0');
        if ((!$allowZero && $quantity === 0) || $quantity > self::MAX) throw ValidationException::withMessages([$field => 'Jumlah di luar batas yang diizinkan.']);
        return $quantity;
    }

    public static function display(int|string $quantity): string
    {
        $q = (int) $quantity; $sign = $q < 0 ? '-' : ''; $q = abs($q);
        $fraction = rtrim(str_pad((string) ($q % 1000), 3, '0', STR_PAD_LEFT), '0');
        return $sign.intdiv($q, 1000).($fraction !== '' ? '.'.$fraction : '');
    }

    public static function convert(int $quantity, int $factor): int
    {
        if ($factor < 1 || $quantity > intdiv(self::MAX * self::SCALE, $factor)) throw ValidationException::withMessages(['items' => 'Jumlah hasil konversi terlalu besar.']);
        $result = $quantity * $factor;
        if ($result % self::SCALE !== 0) throw ValidationException::withMessages(['items' => 'Hasil konversi harus tepat sampai 3 angka desimal.']);
        return intdiv($result, self::SCALE);
    }
}
