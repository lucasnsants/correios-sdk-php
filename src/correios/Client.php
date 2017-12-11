<?php

namespace Correios;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client as HttpClient;
use Correios\Freight;
use Correios\FreightInterface;


class Client implements LoggerAwareInterface, LoggerInterface
{
    /**
     * Serviço de frete.
     *
     * @var \Correios\FreightInterface
     */
    protected $freight;

    /**
     * Cria uma nova instância da classe Client.
     *
     * @param \GuzzleHttp\ClientInterface|null  $http
     * @param \Correios\FreightInterface|null $freight
     */
    public function __construct(
        ClientInterface $http = null,
        FreightInterface $freight = null
    ) {
        $this->http = $http ?: new HttpClient;
        $this->freight = $freight ?: new Freight($this->http);
    }

    /**
     * Serviço de frete dos Correios.
     *
     * @return \Correios\FreightInterface
     */
    public function freight()
    {
        return $this->freight;
    }
}