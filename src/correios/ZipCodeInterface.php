<?php

namespace Correios;

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