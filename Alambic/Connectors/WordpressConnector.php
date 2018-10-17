<?php
namespace App\Extensions\WordpressConnector\Alambic\Connectors;
use Alambic\Exception\ConnectorArgs;
use Alambic\Exception\ConnectorConfig;
use Alambic\Exception\ConnectorInternal;
use GuzzleHttp\Client as Client;
class WordpressConnector extends \Alambic\Connector\AbstractConnector
{
    protected $client;

    public function __invoke($payload=[])
    {
        if (isset($payload["response"])) {
            return $payload;
        }
        $this->setPayload($payload);
        $this->checkConfig();
        $this->client = new Client;
        return $payload["isMutation"] ? $this->execute($payload) : $this->resolve($payload);
    }

    public function resolve($payload=[]){

        if ($this->multivalued) {
            if ($this->filters) {
                $filters = json_encode($this->filters);
            } else {
                $filters = [];
            }
            $res = $this->client->request('GET',$this->config["host"]."/".$this->config["segment"],[
              'http_errors' => false,
              'verify' => false,
              'query' => ['filters' => $filters]
            ]);
            $result=json_decode($res->getBody()->getContents(),true);
            $payload["response"]=$result["items"];
            return $payload;

        } else {
            throw new ConnectorInternal("WIP");
        }

    }

    public function execute($payload=[]){
        throw new ConnectorInternal("WIP");
    }

}
