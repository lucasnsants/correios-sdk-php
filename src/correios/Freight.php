<?php

namespace Correios;

use SimpleXMLElement;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\ClientInterface;
use Correios\Service;
use Correios\WebService;
use Correios\PackageType;
use Correios\FreightInterface;


class Freight implements FreightInterface
{
/**
     * Payload standard.
     *
     * @var array
     */
    protected $defaultPayload = [
        'nCdEmpresa' => '',
        'sDsSenha' => '',
        'nCdServico' => '',
        'sCepOrigem' => '',
        'sCepDestino' => '',
        'nCdFormato' => PackageType::BOX,
        'nVlLargura' => 11,
        'nVlAltura' => 2,
        'nVlPeso' => 0,
        'nVlComprimento' => 16,
        'nVlDiametro' => 0,
        'sCdMaoPropria' => 'N',
        'nVlValorDeclarado' => 0,
        'sCdAvisoRecebimento' => 'N',
    ];

    /**
     * Request playload.
     *
     * @var array
     */
    protected $payload = [];

    /**
     * Send objects.
     *
     * @var array
     */
    protected $items = [];

    private $width;
    private $height;
    private $length;
    private $weight;

    /**
     * HTTP Client.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $http;

    /**
     * Creates a new class instance.
     *
     * @param \GuzzleHttp\ClientInterface $http
     */
    public function __construct(ClientInterface $http)
    {
        $this->http = $http;
    }

    /**
     * Payload da requisição para o webservice dos Correios.
     *
     * @return array
     */
    public function payload()
    {
        $this->setFreightDimensionsOnPayload();

        return array_merge($this->defaultPayload, $this->payload);
    }

    /**
     * Origin CEO.
     *
     * @param  string $zipCode
     *
     * @return self
     */
    public function origin($zipCode)
    {
        $this->payload['sCepOrigem'] = preg_replace('/[^0-9]/', null, $zipCode);

        return $this;
    }

    /**
     * Destination CEP.
     *
     * @param  string $zipCode
     *
     * @return self
     */
    public function destination($zipCode)
    {
        $this->payload['sCepDestino'] = preg_replace('/[^0-9]/', null, $zipCode);

        return $this;
    }

    /**
     * Calculates Services.
     *
     * @param  int ...$services
     *
     * @return self
     */
    public function services(...$services)
    {
        $this->payload['nCdServico'] = implode(',', array_unique($services));

        return $this;
    }

    /**
     * Administrative code with ECT. The code is available on the
     * body of the contract signed with the Post Office.
     *
     * Password for access to the service, associated with your administrative code,
     * the initial password corresponds to the first 8 digits of the CNPJ informed in the contract.
     *
     * @param  string $code
     * @param  string $password
     *
     * @return self
     */
    public function credentials($code, $password)
    {
        $this->payload['nCdEmpresa'] = $code;
        $this->payload['sDsSenha'] = $password;

        return $this;
    }

    /**
     * Formato of encomenda (Caixa, pacote, rolo, prisma or envelope).
     *
     * @param  int $format
     *
     * @return self
     */
    public function package($format)
    {
        $this->payload['nCdFormato'] = $format;

        return $this;
    }

    /**
     * Indicate if the order will be delivered with the additional service by hand.
     *
     * @param  bool $useOwnHand
     *
     * @return self
     */
    public function useOwnHand($useOwnHand)
    {
        $this->payload['sCdMaoPropria'] = (bool) $useOwnHand ? 'S' : 'N';

        return $this;
    }

    /**
     * Indique se a encomenda será entregue com o serviço adicional valor declarado,
     * deve ser apresentado o valor declarado desejado, em reais.
     *
     * @param  int|float $value
     *
     * @return self
     */
    public function declaredValue($value)
    {
        $this->payload['nVlValorDeclarado'] = floatval($value);

        return $this;
    }

    /**
     * Dimensions, weight and quantity of the item.
     *
     * @param  int|float $width
     * @param  int|float $height
     * @param  int|float $length
     * @param  int|float $weight
     * @param  int       $quantity
     *
     * @return self
     */
    public function item($width, $height, $length, $weight)
    {
        $this->width = $width;
        $this->height = $height;
        $this->length = $length;
        $this->weight = $weight;

        return $this;
    }

    /**
     * Calculates prices and deadlines with the Post Office.
     *
     * @return array
     */
    public function calculate()
    {
        $response = $this->http->get(WebService::CALC_PRICE_DEADLINE, [
            'query' => $this->payload(),
        ]);

        $services = $this->fetchCorreiosServices($response);

        return array_map([$this, 'transformCorreiosService'], $services);
    }

    public function setLogger(LoggerInterface $logger = null) {
        $this->logger = $logger;
    }
    
    /* Helper function to log data within the Client */
    private function logMessage($message) {
        if ($this->logger) {
            $this->logger->debug($message);
        }
    }

    /**
     * Calculates freight width, height, length, weight and volume in payload.
     *
     * @return self
     */
    protected function setFreightDimensionsOnPayload()
    {
            $this->payload['nVlLargura'] = $this->width;
            $this->payload['nVlAltura'] = $this->height;
            $this->payload['nVlComprimento'] = $this->length;
            $this->payload['nVlDiametro'] = 0;
            $this->payload['nVlPeso'] = $this->weight;

        return $this;
    }

    /**
     * Calcula o volume do frete com base no comprimento, largura e altura dos itens.
     *
     * @return int|float
     */
    protected function volume()
    {
        return ($this->length() * $this->width() * $this->height()) / 6000;
    }

    /**
     * Calcula qual valor (volume ou peso físico) deve ser
     * utilizado como peso do frete na requisição final.
     *
     * @return int|float
     */
    protected function useWeightOrVolume()
    {
        if ($this->volume() < 10 || $this->volume() <= $this->weight()) {
            return $this->weight();
        }

        return $this->volume();
    }

    /**
     * Extrai todos os serviços retornados no XML de resposta dos Correios.
     *
     * @param  \GuzzleHttp\Psr7\Response $response
     *
     * @return array
     */
    protected function fetchCorreiosServices(Response $response)
    {
        $xml = simplexml_load_string($response->getBody()->getContents());
        $results = json_decode(json_encode($xml->Servicos))->cServico;

        if (! is_array($results)) {
            return [get_object_vars($results)];
        }

        return array_map('get_object_vars', $results);
    }

    /**
     * Transforma um serviço dos Correios em um array mais limpo,
     * legível e fácil de manipular.
     *
     * @param  array  $service
     *
     * @return array
     */
    protected function transformCorreiosService(array $service)
    {
        $error = [];

        if ($service['Erro'] != 0) {
            $error = [
                'code' => $service['Erro'],
                'message' => $service['MsgErro'],
            ];
        }

        return [
            'name' => $this->friendlyServiceName($service['Codigo']),
            'code' => $service['Codigo'],
            'price' => floatval(str_replace(',', '.', $service['Valor'])),
            'deadline' => intval($service['PrazoEntrega']),
            'error' => $error,
        ];
    }

    /**
     * Nome dos seviços (Sedex, PAC...) com base no código.
     *
     * @param  string $code
     *
     * @return string
     */
    protected function friendlyServiceName($code)
    {
        return [
            intval(Service::PAC) => 'PAC',
            intval(Service::PAC_CONTRATO) => 'PAC',
            intval(Service::SEDEX) => 'Sedex',
            intval(Service::SEDEX_CONTRATO) => 'Sedex',
            intval(Service::SEDEX_A_COBRAR) => 'Sedex a Cobrar',
            intval(Service::SEDEX_10) => 'Sedex 10',
            intval(Service::SEDEX_HOJE) => 'Sedex Hoje',
        ][intval($code)];
    }
}