<?php
namespace App\Extensions\WordpressConnector\Controllers;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use GuzzleHttp\Client as Client;
class WordpressController extends BaseController
{
    public function introspect(Request $request)
    {
        if(empty(config("gdsNamespace"))){
            abort(403,'Use of this API is not authorized for this namespace');
        }
        $host=$request->input("host",null);
        $name=$request->input("name",null);
        if(empty($host)||empty($name)){
            abort(400,'Missing required params');
        }
        $client = new Client;
        $res = $client->request('GET',$host.'/wp-json/ampize/v1/model',[
            'http_errors' => false,
            'verify' => false
        ]);
        $mapping=json_decode($res->getBody()->getContents(),true);
        $model=[];
        foreach($mapping as $type => $typeDef) {
            $typeName = $name."_".$type;
            $model[$typeName] = $mapping[$type];
            $model[$typeName]["name"] = $typeName;
            if (isset($model[$typeName]["singleEndpoint"]["name"])) $model[$typeName]["singleEndpoint"]["name"] = $name."_".$model[$typeName]["singleEndpoint"]["name"];
            if (isset($model[$typeName]["multiEndpoint"]["name"])) $model[$typeName]["multiEndpoint"]["name"] = $name."_".$model[$typeName]["multiEndpoint"]["name"];
            if (isset($model[$typeName]["connector"])) $model[$typeName]["connector"]["type"] = $name;
        }
        return response()->json($model);
    }
}
