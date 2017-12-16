<?php

namespace Correios\Contracts;

interface ZipCodeInterface
{
    /**
     * Find streef for CEP.
     *
     * @param  string $zipcode
     *
     * @return array
     */
    public function find($zipcode);
}