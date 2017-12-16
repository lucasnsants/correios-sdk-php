<?php

namespace Correios\Services;

use GuzzleHttp\ClientInterface;
use Correios\WebService;
use Correios\ZipCodeInterface;

class ZipCode implements ZipCodeInterface
{
    /**
     * Cliente HTTP
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $http;

    /**
     * CEP
     *
     * @var string
     */
    protected $zipcode;

    /**
     * XML quest.
     *
     * @var string
     */
    protected $body;

    /**
     * Response request webservice correios.
     *
     * @var \GuzzleHttp\Psr7\Response
     */
    protected $response;

    /**
     * XML formart response.
     *
     * @var array
     */
    protected $parsedXML;

    /**
     * create new instance.
     *
     * @param ClientInterface $http
     */
    public function __construct(ClientInterface $http)
    {
        $this->http = $http;
    }

    /**
     * Find street for CEP.
     *
     * @param  string $zipcode
     *
     * @return array
     */
    public function find($zipcode)
    {
        $this->setZipCode($zipcode)
            ->buildXMLBody()
            ->sendWebServiceRequest()
            ->parseXMLFromResponse();

        if ($this->hasErrorMessage()) {
            return $this->fetchErrorMessage();
        }

        return $this->fetchZipCodeAddress();
    }

    /**
     * Set CEP.
     *
     * @param string $zipcode
     *
     * @return self
     */
    protected function setZipCode($zipcode)
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    /**
     * Request body XML.
     *
     * @return self
     */
    protected function buildXMLBody()
    {
        $zipcode = preg_replace('/[^0-9]/', null, $this->zipcode);
        $this->body = trim('
            <?xml version="1.0"?>
            <soapenv:Envelope
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                xmlns:cli="http://cliente.bean.master.sigep.bsb.correios.com.br/">
                <soapenv:Header/>
                <soapenv:Body>
                    <cli:consultaCEP>
                        <cep>'.$zipcode.'</cep>
                    </cli:consultaCEP>
                </soapenv:Body>
            </soapenv:Envelope>
        ');

        return $this;
    }

    /**
     * Call webservice Correios
     * save response of webserivce Correios.
     *
     * @return self
     */
    protected function sendWebServiceRequest()
    {
        $this->response = $this->http->post(WebService::SIGEP, [
            'http_errors' => false,
            'body' => $this->body,
            'headers' => [
                'Content-Type' => 'application/xml; charset=utf-8',
                'cache-control' => 'no-cache',
            ],
        ]);

        return $this;
    }

    /**
     * Formart response XML.
     *
     * @return self
     */
    protected function parseXMLFromResponse()
    {
        $xml = $this->response->getBody()->getContents();
        $parse = simplexml_load_string(str_replace([
            'soap:', 'ns2:',
        ], null, $xml));

        $this->parsedXML = json_decode(json_encode($parse->Body), true);

        return $this;
    }

    /**
     * Is exists messsagem of erros
     *
     * @return bool
     */
    protected function hasErrorMessage()
    {
        return array_key_exists('Fault', $this->parsedXML);
    }

    /**
     * Get message error response webservice of Correios.
     *
     * @return array
     */
    protected function fetchErrorMessage()
    {
        return [
            'error' => $this->messages($this->parsedXML['Fault']['faultstring']),
        ];
    }

    /**
     * Message errors.
     *
     * @param  string $faultString
     *
     * @return string
     */
    protected function messages($faultString)
    {
        return [
            'CEP NAO ENCONTRADO' => 'CEP não encontrado',
        ][$faultString];
    }

    /**
     * Get response street of XML.
     *
     * @return array
     */
    protected function fetchZipCodeAddress()
    {
        $address = $this->parsedXML['consultaCEPResponse']['return'];
        $zipcode = preg_replace('/^([0-9]{5})([0-9]{3})$/', '${1}-${2}', $address['cep']);
        $complement = array_values(array_filter([
            $address['complemento'], $address['complemento2']
        ]));

        return [
            'zipcode' => $zipcode,
            'street' => $address['end'],
            'complement' => $complement,
            'district' => $address['bairro'],
            'city' => $address['cidade'],
            'uf' => $address['uf'],
        ];
    }
}