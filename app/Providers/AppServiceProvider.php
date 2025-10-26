<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ImageKitService;
use App\Services\ImageService;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   *
   * @return void
   */
  public function register()
  {
    $this->app->singleton(ImageKitService::class, function ($app) {
      return new ImageKitService();
    });

    $this->app->singleton(ImageService::class, function ($app) {
      return new ImageService($app->make(ImageKitService::class));
    });
  }

  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot()
  {
    //
  }
}
