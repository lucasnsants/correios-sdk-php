<?php

use Correios\Client;
use Correios\Service;

require 'vendor/autoload.php';

// echo "test";

$correios = new Client;

// print_r($correios->zipcode()->find('05581-001'));
echo '<pre>';
print_r($correios->freight()
->origin('01127-000')
->destination('87047-230')
->credentials('16078128', 'mdrrwp')
->services(Service::SEDEX, Service::PAC)
->item(11, 2, 16, 30, 1) // largura, altura, comprimento, peso e quantidade
->calculate());