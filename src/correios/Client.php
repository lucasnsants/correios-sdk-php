<?php

namespace Correios;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client as HttpClient;
use Correios\Freight;
use Correios\ZipCode;
use Correios\FreightInterface;
use Correios\ZipCodeInterface;


class Client
{
    /**
     * Serviço de frete.
     *
     * @var \Correios\FreightInterface
     */
    protected $freight;

    /**
     * Serviço de CEP.
     *
     * @var \Correios\ZipCodeInterface
     */
    protected $zipcode;

    /**
     * Cria uma nova instância da classe Client.
     *
     * @param \GuzzleHttp\ClientInterface|null  $http
     * @param \Correios\FreightInterface|null $freight
     * @param \Correios\ZipCodeInterface|null $zipcode
     */
    public function __construct(
        ClientInterface $http = null,
        FreightInterface $freight = null,
        ZipCodeInterface $zipcode = null
    ) {
        $this->http = $http ?: new HttpClient;
        $this->freight = $freight ?: new Freight($this->http);
        $this->zipcode = $zipcode ?: new ZipCode($this->http);
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

    /**
     * Serviço de CEP dos Correios.
     *
     * @return \Correios\ZipCodeInterface
     */
    public function zipcode()
    {
        return $this->zipcode;
    }
}