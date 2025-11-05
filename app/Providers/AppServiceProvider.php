<?php

namespace App\Providers;

use App\Contracts\{
    UploadRepositoryContract, ProductRepositoryContract, UploadProcessorContract
};
use App\Repositories\{UploadRepository, ProductRepository};
use App\Services\UploadProcessorService;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
            $this->app->bind(UploadRepositoryContract::class, UploadRepository::class);
            $this->app->bind(ProductRepositoryContract::class, ProductRepository::class);
            $this->app->bind(UploadProcessorContract::class, UploadProcessorService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
