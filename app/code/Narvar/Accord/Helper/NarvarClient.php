<?php

namespace Narvar\Accord\Helper;

use Laminas\Http\Client;
use Narvar\Accord\Helper\AccordException;

class NarvarClient
{
    private $client;

    private $logger;

    /**
    * Constructor
    *
    * @param Client        $client        Laminas http client.
    */
    public function __construct(
        Client $client
    ) {
        $this->client = $client;
        $this->client->setOptions(
            [
                "keepalive"  => true,
                "useragent"  => "narvar_accord",
                "persistent" => true,
                'adapter'    => 'Laminas\Http\Client\Adapter\Curl',
            ]
        );
    }

    public function send($api, $method, $body, $headers)
    {
        try {
            $this->client->setUri($api);
            $this->client->setMethod($method);
            $this->client->setRawBody($body);
            $this->client->setHeaders($headers);
            return $this->client->send();
        } catch (\Exception $ex) {
            $errorMessage = $ex->getMessage() . ' in NarvarClient ' . __METHOD__;
            throw new AccordException($errorMessage);
        }
    }
}
