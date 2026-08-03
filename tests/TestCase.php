<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;

abstract class TestCase extends BaseTestCase
{
    /**
     * Bekukan waktu ke jam kerja kasir.
     *
     * Middleware CheckActiveShift dan kedua PosController menutup kasir pada pukul
     * 00:00–06:59 WIB. Tanpa pembekuan ini, seluruh test POS/pending LULUS bila
     * dijalankan siang hari dan GAGAL bila dijalankan dini hari — hasil test
     * bergantung pada jam dinding, bukan pada benar/tidaknya kode.
     *
     * Dibekukan pada 10:00 WIB (zona waktu aplikasi memang Asia/Jakarta) supaya
     * berada jauh di dalam jam buka.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-08-03 10:00:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
