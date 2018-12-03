<?php

namespace App\Extensions\WordpressConnector\Services\Components\Middleware;


class MenuBuilder
{
    public function __invoke($component)
    {

        $root=$component["context"]["page"]["id"];
        if(!empty($component["itemConfig"]["settings"]["root"])){
            $root=$component["itemConfig"]["settings"]["root"];
        } else if (isset($component["itemConfig"]["settings"]["fallbackRoot"])&&$component["itemConfig"]["settings"]["fallbackRoot"]=="parent"&&isset($component["context"]["page"]["parentId"])&&$component["context"]["page"]["parentId"]!="root"){
            $root=$component["context"]["page"]["parentId"];
        }

        //$root= "id de la home ampize";
        /*
        if(isset($component["itemConfig"]["settings"]["baseLevel"])&&$component["itemConfig"]["settings"]["baseLevel"]==2){
            $menuData = app()['AdminGraphQLHandler']->execute('query q{page(siteId:"'.$component["context"]["site"]["id"].'",id:"'.$root.'"){name id children(limit:1000,orderBy:"order",orderByDirection:"ASC"){name order id parentId children(limit:1000,orderBy:"order",orderByDirection:"ASC"){name order id parentId}}}}',null,null);
        } else {
            $menuData = app()['AdminGraphQLHandler']->execute('query q{page(siteId:"'.$component["context"]["site"]["id"].'",id:"'.$root.'"){name id children(limit:1000,orderBy:"order",orderByDirection:"ASC"){name order id parentId}}}',null,null);
        }
        */
        //if(isset($component["itemConfig"]["settings"]["baseLevel"])&&$component["itemConfig"]["settings"]["baseLevel"]==2){
        //    $menuData = app()['AdminGraphQLHandler']->execute('query q{wp_page(isRoot:true){name id children(orderBy:"order",orderByDirection:"ASC",excludeFromMenu:false){name order id parentId children(limit:1000,orderBy:"order",orderByDirection:"ASC"){name order id parentId}}}}',null,null);
        //} else {
            $menuData = app()['DataGraphQLHandler']->execute('query q{wp_pages(parentId:0){name id children(limit:1000,orderBy:"order",orderByDirection:"ASC"){name order id parentId}}}',null,null);
        //}
        $component["pageTree"]=$menuData["data"]["wp_pages"];
        $isSecure=$component["context"]["scheme"]=="https";
        $routeBuilder=app()["RouteBuilder"];
        $component["pageTree"]["url"]=$routeBuilder->getRoute($component["context"]["site"]["id"],$root,$isSecure,$component["context"]['previewMode'],$component["context"]['baseUrl']);
        if (!empty($component['itemConfig']['settings']['imageURL'])) {
            $imageBackgroundCss = ' .ampize-menu-logo {background-image: url(' . $component['itemConfig']['settings']['imageURL'] . ')}';
            $component['context']['page']['cssCode'] = empty($component['context']['page']['cssCode']) ?
                $imageBackgroundCss :
                $component['context']['page']['cssCode'] . $imageBackgroundCss;
        }
        var_dump($component["pageTree"]);
        die();
        foreach ($component["pageTree"] as &$page){
            var_dump($page);
            //$page["url"]=$routeBuilder->getRoute($component["context"]["site"]["id"],$page["id"],$isSecure,$component["context"]['previewMode'],$component["context"]['baseUrl']);
            $page["active"] = $component['context']['page']['id'] == $page['id'];
            if(!empty($page["children"])){
                foreach($page["children"] as &$subpage){
                    if (empty($page["active"])) {
                        $page["active"] = $component['context']['page']['id'] == $subpage['id'];
                    }
                    $subpage["active"] = $component['context']['page']['id'] == $subpage['id'];
                    //$subpage["url"]=$routeBuilder->getRoute($component["context"]["site"]["id"],$subpage["id"],$isSecure,$component["context"]['previewMode'],$component["context"]['baseUrl']);
                }
            }
        }

        return $component;
    }



}
