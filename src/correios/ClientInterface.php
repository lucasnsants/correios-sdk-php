<?php

namespace Correios;

interface ClientInterface
{
    /**
     * Serviço de frete dos Correios.
     *
     * @return \Correios\FreightInterface
     */
    public function freight();
}