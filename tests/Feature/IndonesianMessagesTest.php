<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndonesianMessagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->setLocale('id');
    }

    public function test_form_validation_uses_indonesian_and_replaces_placeholders(): void
    {
        $validator = Validator::make([
            'email' => 'bukan-email',
            'date' => '2026-08-04',
            'occurred_time' => '25:00',
        ], [
            'name' => ['required'],
            'email' => ['email'],
            'date' => ['date_format:Y-m-d', 'before_or_equal:2026-08-03'],
            'occurred_time' => ['date_format:H:i'],
        ]);

        $this->assertSame([
            'name' => ['nama wajib diisi.'],
            'email' => ['email harus berupa alamat email yang valid.'],
            'date' => ['tanggal harus berupa tanggal pada atau sebelum 2026-08-03.'],
            'occurred_time' => ['waktu kejadian harus mengikuti format H:i.'],
        ], $validator->errors()->toArray());
    }

    public function test_date_ranges_use_translated_field_names(): void
    {
        $validator = Validator::make([
            'date_from' => '2026-08-03',
            'date_to' => '2026-08-02',
        ], [
            'date_from' => ['date_format:Y-m-d'],
            'date_to' => ['date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $this->assertSame(
            'tanggal akhir harus berupa tanggal pada atau setelah tanggal awal.',
            $validator->errors()->first('date_to'),
        );
    }

    public function test_transaction_validation_replaces_nested_positions_and_numeric_limits(): void
    {
        $validator = Validator::make([
            'items' => [['quantity' => 0]],
            'amount_paid' => 9000,
            'grand_total' => 10000,
        ], [
            'items.*.quantity' => ['integer', 'min:1'],
            'amount_paid' => ['numeric', 'gte:grand_total'],
        ]);

        $this->assertSame('jumlah pada item ke-1 harus bernilai sedikitnya 1.', $validator->errors()->first('items.0.quantity'));
        $this->assertSame('jumlah pembayaran harus lebih besar dari atau sama dengan 10000.', $validator->errors()->first('amount_paid'));
    }

    public function test_authentication_and_password_reset_messages_are_translated(): void
    {
        $this->assertSame('Email atau kata sandi tidak sesuai.', __('auth.failed'));
        $this->assertSame('Kata sandi yang dimasukkan salah.', __('auth.password'));
        $this->assertSame('Terlalu banyak percobaan masuk. Coba lagi dalam 60 detik.', __('auth.throttle', ['seconds' => 60]));
        $this->assertSame('Kata sandi berhasil diatur ulang.', __('passwords.reset'));
        $this->assertSame('Tautan untuk mengatur ulang kata sandi telah dikirim ke email Anda.', __('passwords.sent'));
        $this->assertSame('Tunggu sebentar sebelum mencoba kembali.', __('passwords.throttled'));
        $this->assertSame('Token untuk mengatur ulang kata sandi tidak valid atau sudah kedaluwarsa.', __('passwords.token'));
        $this->assertSame('Tidak ada pengguna yang terdaftar dengan alamat email tersebut.', __('passwords.user'));
    }
}
