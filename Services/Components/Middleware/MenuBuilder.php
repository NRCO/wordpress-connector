<?php

namespace App\Extensions\WordpressConnector\Services\Components\Middleware;

class MenuBuilder
{
    public function __invoke($component)
    {
        $isSecure=$component["context"]["scheme"]=="https";

        $component["pageTree"]["url"]="/";
        $component["pageTree"]["name"]="Home";
        if (!empty($component['itemConfig']['settings']['imageURL'])) {
            $imageBackgroundCss = ' .ampize-menu-logo {background-image: url(' . $component['itemConfig']['settings']['imageURL'] . ')}';
            $component['context']['page']['cssCode'] = empty($component['context']['page']['cssCode']) ?
                $imageBackgroundCss :
                $component['context']['page']['cssCode'] . $imageBackgroundCss;
        }
        $routeBuilder=app()["WPRouteBuilder"];
        $menuData = app()['DataGraphQLHandler']->execute('query q{wp_pages(parentId:0){name id type urlSegment itemId children(limit:1000,orderBy:"order",orderByDirection:"ASC"){name order id parentId itemId type urlSegment}}}',null,null);
        foreach ($menuData["data"]["wp_pages"] as $page){
            $destinationPage = $this->getDestinationPage($page, $component['itemConfig']['settings']);
            $page["url"]=$routeBuilder->getRoute($component["context"]["site"]["id"],$destinationPage,$isSecure,$component["context"]['previewMode'],$component["context"]['baseHost'],$page["urlSegment"],$page["itemId"]);
            $page["active"] = $component['context']['page']['id'] == $page['id'];
            foreach ($page["children"] as &$subpage) {
                $destinationPage = $this->getDestinationPage($subpage, $component['itemConfig']['settings']);
                $subpage["url"] = $routeBuilder->getRoute($component["context"]["site"]["id"],$destinationPage,$isSecure,$component["context"]['previewMode'],$component["context"]['baseHost'],$subpage["urlSegment"],$subpage["itemId"]);
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
