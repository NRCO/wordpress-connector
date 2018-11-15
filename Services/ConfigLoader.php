<?php
namespace App\Extensions\WordpressConnector\Services;
use Alambic\Exception\Config;
class ConfigLoader
{
    protected $jsonErrorMessages = [
        JSON_ERROR_NONE => 'No error has occurred',
        JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
        JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
        JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
        JSON_ERROR_SYNTAX => 'Syntax error',
        JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
    ];
    public function loadConfig(&$alambicConfig){
        if(empty(config("gdsNamespace"))){
            return;
        }
        $connectors=app()['AdminGraphQLHandler']->execute("query q{wpConnectors(limit:10000){name jsonModel host useCache defaultCacheTTL}}",null,null);
        if(empty($connectors["data"]["wpConnectors"])){
            return;
        }
        foreach ($connectors["data"]["wpConnectors"] as $wpConnectorDef){
            if(isset($wpConnectorDef["jsonModel"])){
                $decodedModel=json_decode(str_replace("'",'"',$wpConnectorDef["jsonModel"]),true);
                if(!$decodedModel){
                    throw new Config("JSON decode error model for WP Connector ".$wpConnectorDef["name"]." : ".$this->jsonErrorMessages[json_last_error()]);
                }
                foreach($decodedModel as $model){
                    if(isset($model["connector"]["type"])){
                        $model["connector"]["type"]=$wpConnectorDef["name"];
                    }
                    $model["sourceTypeLabel"] = "WordPress";
                }
                $alambicConfig["alambicTypeDefs"] = array_merge($alambicConfig["alambicTypeDefs"] , $decodedModel);
                unset($wpConnectorDef["jsonModel"]);
            }
            if(isset($wpConnectorDef["useCache"])&&$wpConnectorDef["useCache"]&&config("installedFeatures")["redisCache"]){
                $wpConnectorDef["prePipeline"]=["RedisCache\\RedisCache"];
                $wpConnectorDef["postPipeline"]=["RedisCache\\RedisCache"];
                $wpConnectorDef["redisCacheHost"]=getenv("redisCacheHost") ? getenv("redisCacheHost") : "redis";
            }
            $wpConnectorDef["connectorClass"]="App\\Extensions\\WordpressConnector\\Alambic\\Connectors\\WordpressConnector";
            $alambicConfig["alambicConnectors"][$wpConnectorDef["name"]]=$wpConnectorDef;
        }

    }
}
