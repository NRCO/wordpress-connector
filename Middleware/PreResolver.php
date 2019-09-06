<?php
namespace App\Extensions\WordpressConnector\Middleware;
use Closure;
use Illuminate\Http\Request;

class PreResolver
{
    public function handle(Request $request,Closure $next)
    {
        $host=$request->getHost();
        $path=$request->path();
        $siteData=app()['AdminGraphQLHandler']->execute('query q{site(host:"'.$host.'"){name  autoPromptConsent displayConsent consentCode checkConsentHref notFoundPage enableNotifSub oneSignalAppId injectFooter injectMenu botIndexing embeddedFooter embeddedFooterOverride embeddedMenu {root templateOverride imageURL displayRoot fallbackRoot baseLevel} lang stateJson accessJson dateFormat manifest analyticsVars analyticsType useDetailCannonical publisherName publisherLogo defaultImage defaultAuthorName homePageId id host cssCode breakpoint faviconUrl certificate aliases seoTitle seoDescription seoKeywords experimentEnable experimentSticky experimentVariants}}',null,null);
        if(empty($siteData["data"]["site"])){
            abort(404,"Site not found");
        }
        $site=$siteData["data"]["site"];
        //must use config from site in final version
        if(!empty(config("ampizeRouteRedirects"))){
            foreach (config("ampizeRouteRedirects") as $routeRedirect){
                $hasMatch=false;
                foreach ($routeRedirect["patterns"] as $pattern){
                    if(preg_match($pattern,$path)){
                        $hasMatch=true;
                        break;
                    }
                }
                if($hasMatch){
                    $input=$request->input();
                    $uri="https://".$request->getHost().$routeRedirect["path"];
                    if($routeRedirect["redirectType"]=="asParam"){
                        $input[$routeRedirect["redirectParam"]]=$path;
                        $explodedPath=explode('/',$path);
                        if(!empty($explodedPath)){
                            $input['categoryLabel']=ucfirst(array_pop($explodedPath));
                        }
                    } elseif ($routeRedirect["redirectType"]=="asDetailSegment"){
                        $uri=$uri."/".urlencode(urlencode($path));
                    } else {
                        abort(500,'Unknown redirect type');
                    }
                    $request2 = Request::create($uri, 'GET', $input);
                    if($request->headers->has('SaveToCache')){
                        $request2->headers->set('SaveToCache', 1);
                    }
                    return app()->dispatch($request2)->getContent();
                }
            }
        }
        return $next($request);
    }
}
