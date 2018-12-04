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
        $component["pageTree"]["url"]="/";
        $component["pageTree"]["name"]="Home";
        if (!empty($component['itemConfig']['settings']['imageURL'])) {
            $imageBackgroundCss = ' .ampize-menu-logo {background-image: url(' . $component['itemConfig']['settings']['imageURL'] . ')}';
            $component['context']['page']['cssCode'] = empty($component['context']['page']['cssCode']) ?
                $imageBackgroundCss :
                $component['context']['page']['cssCode'] . $imageBackgroundCss;
        }
        $routeBuilder=app()["RouteBuilder"];
        $menuData = app()['DataGraphQLHandler']->execute('query q{wp_pages(parentId:0){name id type urlSegment children(limit:1000,orderBy:"order",orderByDirection:"ASC"){name order id parentId type urlSegment}}}',null,null);
        foreach ($menuData["data"]["wp_pages"] as $page){
            $destinationPage = $this->getDestinationPage($page, $component['itemConfig']['settings']);
            $page["url"]=$routeBuilder->getRoute($component["context"]["site"]["id"],$destinationPage,$isSecure,$component["context"]['previewMode'],$component["context"]['baseUrl'],$page["urlSegment"]);
            $page["active"] = $component['context']['page']['id'] == $page['id'];
            foreach ($page["children"] as &$subpage) {
                $destinationPage = $this->getDestinationPage($subpage, $component['itemConfig']['settings']);
                $subpage["url"] = $routeBuilder->getRoute($component["context"]["site"]["id"],$subpage["id"],$isSecure,$component["context"]['previewMode'],$component["context"]['baseUrl'],$page["urlSegment"]);
            }
            $component["pageTree"]["children"][] = $page;
        }
        return $component;
    }

    private function getDestinationPage($page, $componentSettings) {
        switch($page["type"]) {
            case "list":
                $destinationPage = $componentSettings['listPage'];
                break;
            case "item":
                $destinationPage = $componentSettings['detailPage'];
                break;
            default:
                $destinationPage = NULL;
                break;
        }
        return $destinationPage;
    }

}
