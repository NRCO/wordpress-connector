<?php
namespace App\Extensions\WordpressConnector\Providers;
use App\Extensions\WordpressConnector\Services\ConfigLoader;
use App\Extensions\WordpressConnector\Services\Routebuilder;
use Illuminate\Support\ServiceProvider;
class WPProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('WPConfigLoader', function ($app) {
            return new ConfigLoader();
        });
        $this->app->singleton('WPRouteBuilder', function ($app) {
            return new RouteBuilder();
        });
    }
}
