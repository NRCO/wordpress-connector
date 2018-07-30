<?php
namespace App\Extensions\WordpressConnector\Alambic\Connectors;
use Alambic\Exception\ConnectorArgs;
use Alambic\Exception\ConnectorConfig;
use Alambic\Exception\ConnectorInternal;
use GuzzleHttp\Client as Client;
class WordpressConnector
{
    protected $client;
    public function __invoke($payload=[])
    {
        if (isset($payload["response"])) {
            return $payload;
        }
        $configs=isset($payload["configs"]) ? $payload["configs"] : [];
        $baseConfig=isset($payload["connectorBaseConfig"]) ? $payload["connectorBaseConfig"] : [];
        $config = array_merge($baseConfig, $configs);
        if(empty($config["host"])){
            throw new ConnectorConfig("Missing required config");
        }
        return $payload["isMutation"] ? $this->execute($payload,$config) : $this->resolve($payload,$config);
    }
    public function resolve($payload=[],$config){
        $multivalued=isset($payload["multivalued"]) ? $payload["multivalued"] : false;
        if($multivalued){
            throw new ConnectorInternal("WIP");
        }
        $args=isset($payload["args"]) ? $payload["args"] : [];
        if(empty($args["id"])){
            throw new ConnectorArgs("id is required");
        }
        $client =new Client;
        $res = $client->request('GET','http://'.$config["host"].'/api/v1/...',[
            'http_errors' => false,
            'verify' => false
        ]);
        $result=json_decode($res->getBody()->getContents(),true);
        $payload["response"]=$result["data"];
        return $payload;
    }
    public function execute($payload=[],$config){
        throw new ConnectorInternal("WIP");
    }
}
