<?php

use Correios\Client;

require 'vendor/autoload.php';

$correios = new Client;

$correios->zipcode()
    ->find('01001-000');
