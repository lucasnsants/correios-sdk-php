<?php

namespace Correios;

interface ClientInterface
{
    /**
     * Service freight of Correios.
     *
     * @return \Correios\FreightInterface
     */
    public function freight();
}