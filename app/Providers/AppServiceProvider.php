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
        // Allow Excel-HTML .xls (MIME text/html) through Livewire's temp upload endpoint.
        // Component-level rules still enforce .xls / .xlsx / .csv by original filename.
        config([
            'livewire.temporary_file_upload.rules' => ['required', 'file', 'max:20480'],
        ]);
    }
}
