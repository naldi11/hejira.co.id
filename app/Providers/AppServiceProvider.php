<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pastikan semua JsonResource tidak membungkus data dalam key 'data'
        // agar Inertia dapat langsung mengakses properti (misal: transfer.transfer_number)
        JsonResource::withoutWrapping();

        // Catatan: view composer untuk 'layouts.gudang' / 'layouts.hendhys' /
        // 'layouts.owner' sudah dihapus. Dua yang pertama tidak pernah ada filenya,
        // dan layouts.hendhys ikut terhapus bersama alur POS blade lama (pra-React).
        // Hitungan badge pending sekarang disediakan lewat
        // Api\NotificationController::getCounts yang dipakai UI Inertia/React.
    }
}
