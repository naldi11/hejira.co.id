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

        view()->composer(['layouts.gudang', 'layouts.hendhys', 'layouts.owner'], function ($view) {
            $gudang_pending_count = \App\Models\TransferRequest::where('status', 'pending')->count();
            $hendhys_pusat_pending_count = \App\Models\HendhysBranchRequest::where('status', 'pending')->count();
            
            $view->with([
                'gudang_pending_count' => $gudang_pending_count,
                'hendhys_pusat_pending_count' => $hendhys_pusat_pending_count
            ]);
        });
    }
}
