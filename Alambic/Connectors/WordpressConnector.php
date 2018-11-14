<?php
namespace App\Extensions\WordpressConnector\Alambic\Connectors;
use Alambic\Exception\ConnectorArgs;
use Alambic\Exception\ConnectorConfig;
use Alambic\Exception\ConnectorInternal;
use GuzzleHttp\Client as Client;
class WordpressConnector extends \Alambic\Connector\AbstractConnector
{
    protected $client;
    protected $limit = 10;

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
              'query' => [
                'filters' => $filters,
                'limit' => $this->limit,
                'start' => $this->start,
                'orderby' => $this->orderBy,
                'order' => $this->orderByDirection
              ]
            ]);
            $result=json_decode($res->getBody()->getContents(),true);
            $payload["response"]=$result["items"];
            return $payload;

        } else {
            $res = $this->client->request('GET',$this->config["host"]."/".$this->injectArgsInSegment($this->args, $this->config["detailSegment"]),[
              'http_errors' => false,
              'verify' => false
            ]);
            $result=json_decode($res->getBody()->getContents(),true);
            $payload["response"]= (!empty($result)) ? $result : null;
            return $payload;
        }
    }

    private function injectArgsInSegment(&$args, $segment) {
        foreach($args as $key => $value) {
            $segment = str_replace("{".$key."}", $value, $segment, $count);
            if ($count>0) unset($args[$key]);
        }
        $segment = preg_replace("/\{[^}]+\}/","",$segment);
        return $segment;
    }

    public function execute($payload=[]){
        throw new ConnectorInternal("WIP");
    }

}
