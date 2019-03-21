<?php
$authorizedDomains=[
    "cloud.ampize.me",
    "tmv.ampize.me",
    "www.tmvtours.fr",
    "tmv-qual.nrco.fr",
    "ampize-qual.nrco.fr",
    "nramp-qual.nrco.fr",
    "m.lanouvellerepublique.fr",
    "testbo.ampize.me",
    "test.ampize.me",
    "boampize.test",
    "ampize.test",
    "ampize.nrco.fr"
];
if(!empty($_SERVER["HTTP_HOST"])&&in_array($_SERVER["HTTP_HOST"],$authorizedDomains)) {
    view()->addLocation(realpath(__DIR__ . '/resources/views'));
    $otherConfigurators = config("alambicConfigurators");
    if (empty($otherConfigurators)) {
        $otherConfigurators = [];
    }
    $otherConfigurators[] = "WPConfigLoader";
    $adminConfigPaths = config("adminAlambicConfigPaths");
    $adminConfigPaths[] = realpath(__DIR__ . '/Alambic/config/system');
    $dataConfigPaths = config("dataAlambicConfigPaths");
    $dataConfigPaths[] = realpath(__DIR__ . '/Alambic/config/custom');
    $wpBoExtraScripts = ["/resource/wp/src/modules/wpConnector.js"];
    $wpBoExtraStyles = [];
    $wpBoExtraModules = ['ampize.wpConnector'];
    $boExtraScripts = !empty(config("boExtraScripts")) ? array_merge(config("boExtraScripts"), $wpBoExtraScripts) : $wpBoExtraScripts;
    $boExtraStyles = !empty(config("boExtraStyles")) ? array_merge(config("boExtraStyles"), $wpBoExtraStyles) : $wpBoExtraStyles;
    $boExtraModules = !empty(config("boExtraModules")) ? array_merge(config("boExtraModules"), $wpBoExtraModules) : $wpBoExtraModules;
    $ampizeComponentConfigPaths = config("ampizeComponentConfigPaths");
    $ampizeComponentConfigPaths[] = realpath(__DIR__ . '/Components');
    $app->register(App\Extensions\WordpressConnector\Providers\WPProvider::class);
    $app->post('/admin/publish-site', ['middleware' => ['configLoader'], "uses" => 'App\Extensions\AmpizeCloud\Controllers\PublishingController@publishSite']);
    /*
    $app->routeMiddleware([
        'routeResolver' => App\Extensions\WordpressConnector\Middleware\WPRouteResolver::class,
    ]);
    */
    config([
        "boExtraScripts" => $boExtraScripts,
        "boExtraStyles" => $boExtraStyles,
        "boExtraModules" => $boExtraModules,
        "alambicConfigurators" => $otherConfigurators,
        "adminAlambicConfigPaths" => $adminConfigPaths,
        "dataAlambicConfigPaths" => $dataConfigPaths,
        "ampizeComponentConfigPaths" => $ampizeComponentConfigPaths,
        "resourceNamespaces.wp" => [
            "path" => realpath(__DIR__ . '/resources/wp/')
        ],
    ]);
    $app->group(['namespace' => 'App\Extensions\WordpressConnector\Controllers'], function ($app) {
        $app->get('/api/admin/wp/introspect', "WordpressController@introspect");
    });
    /*
        if(!isset($app->getRoutes()["GET/{path:.*}"])){
            $app->group(['namespace' => 'App\Http\Controllers'], function ($app) {
            $app->get('/{path:.*}', ['middleware' => 'WPRouteResolver',"uses"=>"MainFrontController@render"]);
        });
    */
    $customRouterDomains = [
        "tmv.ampize.me",
        "tmv-qual.nrco.fr",
        "www.tmvtours.fr"
    ];
    if (!empty($_SERVER["HTTP_HOST"]) && in_array($_SERVER["HTTP_HOST"], $customRouterDomains)) {
        $app->routeMiddleware([
            'preResolver' => App\Extensions\WordpressConnector\Middleware\PreResolver::class,
            'routeResolver' => App\Extensions\WordpressConnector\Middleware\WPRouteResolver::class
        ]);
        if (config('hasFrontCache')) {
            $middlewares = ['readFromCache', 'publicNS', 'configLoader', 'preResolver', 'routeResolver'];
        } else { 
            $middlewares = ['publicNS', 'configLoader', 'preResolver', 'routeResolver'];
        }
        config([
            "latestRoutes" => [
                [
                    "method" => "GET",
                    "uri" => "/{path:.*}",
                    "action" => ['middleware' => $middlewares, "uses" => "App\Http\Controllers\MainFrontController@render"]
                ]
            ],
            "ampizeRouteRedirects" => [
                [
                    "patterns" => ["/category\/+/"],
                    "redirectType" => "asParam",
                    "redirectParam" => "url",
                    "path" => "/category"
                ],
                [
                    "patterns" => ["/^article\/+/"],
                    "redirectType" => "asDetailSegment",
                    "path" => "/detail-article"
                ]
            ]
        ]);
    }
}
