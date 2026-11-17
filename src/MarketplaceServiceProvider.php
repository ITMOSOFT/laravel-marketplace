<?php
namespace ItmoSoft\Marketplace;
use Illuminate\Support\ServiceProvider;
class MarketplaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/itmosoft-marketplace.php', 'itmosoft-marketplace');
        $this->app->singleton(MarketplaceManager::class);
    }
    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/itmosoft-marketplace.php' => config_path('itmosoft-marketplace.php')], 'itmosoft-marketplace-config');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
