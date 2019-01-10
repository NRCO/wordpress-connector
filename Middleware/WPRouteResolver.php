<?php
namespace App\Extensions\WordpressConnector\Middleware;
use Closure;
class WPRouteResolver
{
    public function handle($request,Closure $next)
    {
        $host=$request->getHost();
        $path=$request->path();
        $siteData=app()['AdminGraphQLHandler']->execute('query q{site(host:"'.$host.'"){name autoPromptConsent displayConsent consentCode checkConsentHref notFoundPage enableNotifSub oneSignalAppId injectFooter injectMenu botIndexing embeddedFooter embeddedFooterOverride embeddedMenu {root templateOverride imageURL displayRoot fallbackRoot baseLevel} lang stateJson accessJson dateFormat manifest analyticsVars analyticsType useDetailCannonical publisherName publisherLogo defaultImage defaultAuthorName homePageId id host cssCode breakpoint faviconUrl certificate aliases seoTitle seoDescription seoKeywords experimentEnable experimentSticky experimentVariants}}',null,null);
        if(empty($siteData["data"]["site"])){
            abort(404,"Site not found");
        }
        $site=$siteData["data"]["site"];
        $page=$this->resolvePage($path,$site);
        $detailSegment=null;
        if(!$page){
            if($path=="/"){
                abort(404,"Page not found");
            } else {
                $explodedPath=explode('/',$path);
                $detailSegment=urldecode(urldecode(array_pop($explodedPath)));
                $newPath=implode('/',$explodedPath);
                if($newPath==""){
                    $newPath="/";
                }
                $page=$this->resolvePage($newPath,$site);
                if(!$page){
                    abort(404,"Page not found");
                }
            }
        }
        $categoryLabel=$request->input("categoryLabel",null);
        if(!empty($categoryLabel)){
            $page["seoTitle"]=$categoryLabel;
        }
        $request->merge([
            "currentSite"=> $site,
            "currentPage"=> $page,
            "detailSegment"=> $detailSegment,
            "originalInput"=> $request->input()
        ]);
        return $next($request);
    }

    protected function buildRoutingQuery($pathArray,$siteId,$isFirst=false){
        $includedSegement="";
        $isLast=false;
        if(count($pathArray)>1){
            $includedSegement=$this->buildRoutingQuery(array_slice($pathArray,1),$siteId,false);
        } else {
            $isLast=true;
        }
        if ($isFirst&&$isLast){
            return('query q{page(siteId:"'.$siteId.'",segment:"'.$pathArray[0].'"){id noMenu noFooter name cannonicalURL stateJson cssCode analyticsVars seoTitle seoDescription seoKeywords parentId items{name sizeX sizeY row col itemType itemConfig}}}');
        } elseif ($isFirst) {
            return('query q{page(siteId:"'.$siteId.'",segment:"'.$pathArray[0].'"){'.$includedSegement.'}}');
        } elseif ($isLast) {
            return('children(siteId:"'.$siteId.'",segment:"'.$pathArray[0].'"){id noMenu noFooter name cannonicalURL stateJson cssCode analyticsVars seoTitle seoDescription seoKeywords parentId items{name sizeX sizeY row col itemType itemConfig}}');
        } else {
            return('children(siteId:"'.$siteId.'",segment:"'.$pathArray[0].'"){'.$includedSegement.'}');
        }

    }

    protected function resolvePage($path,$site){
        $page=null;
        if($path=="/"){
            $pageData=app()['AdminGraphQLHandler']->execute('query q{page(siteId:"'.$site['id'].'",id:"'.$site['homePageId'].'"){id noMenu noFooter name cannonicalURL stateJson cssCode analyticsVars seoTitle seoDescription seoKeywords parentId items{name sizeX sizeY row col itemType itemConfig}}}',null,null);
            if(!empty($pageData["data"]["page"])){
                $page=$pageData["data"]["page"];
            } else {
                return null;
            }
        } else {
            $vanityPageData=app()['AdminGraphQLHandler']->execute('query q{page(siteId:"'.$site['id'].'",vanityUrl:"'.$path.'"){id noMenu noFooter name cannonicalURL stateJson cssCode analyticsVars seoTitle seoDescription seoKeywords parentId items{name sizeX sizeY row col itemType itemConfig}}}',null,null);
            if(!empty($vanityPageData["data"]["page"])){
                $page=$vanityPageData["data"]["page"];
            } else {
                $explodedPath=explode('/',$path);
                $routingQuery=$this->buildRoutingQuery($explodedPath,$site['id'],true);
                $routingQueryData=app()['AdminGraphQLHandler']->execute($routingQuery);
                if(empty($routingQueryData['data']['page'])){
                    return null;
                } else {
                    $page=$routingQueryData['data']['page'];
                    $pathLength=count($explodedPath);
                    if($pathLength>1){
                        for ($aux = 1; $aux < $pathLength; $aux++) {
                            if(empty($page["children"][0])){
                                return null;
                            }
                            $page=$page["children"][0];
                        }
                    }
                }
            }
        }
        return $page;
    }
}

