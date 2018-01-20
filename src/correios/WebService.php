<?php

namespace Correios;

abstract class WebService
{
    /**
     * URL of SIGEP webservice Correios.
     */
    const SIGEP = 'https://apphom.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl';

    /**
     * URL of webservice dos Correios for calculate de price e deadline.
     */
    const CALC_PRICE_DEADLINE = 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo';
}