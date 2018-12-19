<?php
namespace App\Extensions\WordpressConnector\Services;
class RouteBuilder
{
    protected $sites = [];
    protected function loadSiteData($siteId){
        $siteData=app()['AdminGraphQLHandler']->execute('query q{site(id:"'.$siteId.'"){name homePageId id host pages(limit:10000){id name parentId segment customSegment vanityUrl}}}',null,null);
        if(empty($siteData["data"]["site"])){
            return null;
        }
        if(!empty($siteData["data"]["site"]["pages"])){
            $keyArray=[];
            foreach($siteData["data"]["site"]["pages"] as $sitePage){
                $keyArray[$sitePage["id"]]=$sitePage;
            }
            $siteData["data"]["site"]["pages"]=$keyArray;
        }
        $this->sites[$siteId]=$siteData["data"]["site"];
    }
    public function getRoute($siteId,$pageId=null,$secure=true,$previewMode=false,$baseUrl="",$urlSegment=NULL,$itemId=NULL){
        if($previewMode){
            $url = config("accessToken") ? $baseUrl.'?pageId='.$pageId.'&siteId='.$siteId.'&access_token='.config("accessToken") : $baseUrl.'?pageId='.$pageId.'&siteId='.$siteId;
            if ($urlSegment) {
                $url.="&url=".$urlSegment;
            }
            if ($itemId) {
                $url.="&detailId=".$itemId;
            }
            return $url;
        }
        $protocol=$secure ? 'https://' : 'http://';
        if ($urlSegment) {
            $url = $protocol.$baseUrl.$urlSegment;
            return $url;
        }
        if(!isset($this->sites[$siteId])){
            $this->loadSiteData($siteId);
        }
        if(!$pageId){
            $pageId=$this->sites[$siteId]["homePageId"];
        }
        $domain=$this->sites[$siteId]["host"];
        if(!isset($this->sites[$siteId]["pages"][$pageId])){
            return null;
        }
        if($pageId==$this->sites[$siteId]["homePageId"]){
            return $protocol.$domain;
        } elseif (!empty($this->sites[$siteId]["pages"][$pageId]["vanityUrl"])){
            return $this->sites[$siteId]["pages"][$pageId]["vanityUrl"][0]!='/' ? $protocol.$domain.'/'.$this->sites[$siteId]["pages"][$pageId]["vanityUrl"] : $protocol.$domain.$this->sites[$siteId]["pages"][$pageId]["vanityUrl"];
        } else {
            $segArray=[$this->sites[$siteId]["pages"][$pageId]["segment"]];
            $currentPage=$this->sites[$siteId]["pages"][$pageId];
            while($currentPage["parentId"]!="root"){
                if(empty($this->sites[$siteId]["pages"][$currentPage["parentId"]])){
                    return null;
                }
                $segArray[]=$this->sites[$siteId]["pages"][$currentPage["parentId"]]["segment"];
                $currentPage=$this->sites[$siteId]["pages"][$currentPage["parentId"]];
            }
            $segment=implode('/',array_reverse($segArray));
            return $protocol.$domain.'/'.$segment;
        }
    }
    public function getBreadCrumb($siteId,$pageId=null,$secure=true,$previewMode=false,$baseUrl=""){
        if(!isset($this->sites[$siteId])){
            $this->loadSiteData($siteId);
        }
        if (!$pageId||$pageId==$this->sites[$siteId]["homePageId"]){
            return [[
                "name"=>$this->sites[$siteId]["pages"][$this->sites[$siteId]["homePageId"]]["name"],
                "url"=>$this->getRoute($siteId,null,$secure,$previewMode,$baseUrl)
            ]];
        } else {
            $breadCrumb=[];
            $currentPage=$this->sites[$siteId]["pages"][$pageId];
            $breadCrumb[]=[
                "name"=>$currentPage["name"],
                "url"=>$this->getRoute($siteId,$currentPage["id"],$secure,$previewMode,$baseUrl)
            ];
            while($currentPage["parentId"]!="root"){
                $currentPage=$this->sites[$siteId]["pages"][$currentPage["parentId"]];
                $breadCrumb[]=[
                    "name"=>$currentPage["name"],
                    "url"=>$this->getRoute($siteId,$currentPage["id"],$secure,$previewMode,$baseUrl)
                ];
            }
            return array_reverse($breadCrumb);
        }
    }
}
