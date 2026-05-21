<?php

namespace App\Providers;

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
