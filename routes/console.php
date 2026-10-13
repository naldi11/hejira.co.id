<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('production:check-stock', function () {
    $monitor = app(\App\Services\ProductionStockMonitor::class)->overview();
    $this->info($monitor['overdue_count'].' produk melewati batas distribusi Hendhys.');
})->purpose('Pantau sisa stok hasil produksi Hendhys setelah 17.00 WIB');

\Illuminate\Support\Facades\Schedule::command('production:check-stock')->everyMinute()->withoutOverlapping();
