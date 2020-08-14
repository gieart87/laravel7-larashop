<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Repositories\Front\Interfaces\CatalogueRepositoryInterface;
use App\Repositories\Front\CatalogueRepository;

class FrontRepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            CatalogueRepositoryInterface::class,
            CatalogueRepository::class
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
