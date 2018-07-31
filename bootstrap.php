<?php

    $otherConfigurators=config("alambicConfigurators");
    if(empty($otherConfigurators)){
        $otherConfigurators=[];
    }
    $otherConfigurators[]="WPConfigLoader";
    $adminConfigPaths=config("adminAlambicConfigPaths");
    $adminConfigPaths[]=realpath(__DIR__.'/Alambic/config/system');
    $dataConfigPaths=config("dataAlambicConfigPaths");
    $dataConfigPaths[]=realpath(__DIR__.'/Alambic/config/custom');
    $wpBoExtraScripts = ["/resource/wp/src/modules/wpConnector.js"];
    $wpBoExtraStyles = [];
    $wpBoExtraModules = ['ampize.wpConnector'];
    $boExtraScripts = !empty(config("boExtraScripts")) ? array_merge(config("boExtraScripts"), $wpBoExtraScripts) : $wpBoExtraScripts;
    $boExtraStyles = !empty(config("boExtraStyles")) ? array_merge(config("boExtraStyles"), $wpBoExtraStyles) : $wpBoExtraStyles;
    $boExtraModules = !empty(config("boExtraModules")) ? array_merge(config("boExtraModules"), $wpBoExtraModules) : $wpBoExtraModules;
    $app->register(App\Extensions\WordpressConnector\Providers\WPProvider::class);
    config([
        "boExtraScripts" => $boExtraScripts,
        "boExtraStyles" => $boExtraStyles,
        "boExtraModules" => $boExtraModules,
        "alambicConfigurators" => $otherConfigurators,
        "installedFeatures.wpConnector"=>true,
        "adminAlambicConfigPaths"=>$adminConfigPaths,
        "dataAlambicConfigPaths"=>$dataConfigPaths,
        "resourceNamespaces.wp"=>[
            "path"=>realpath(__DIR__.'/resources/wp/')
        ],
    ]);
