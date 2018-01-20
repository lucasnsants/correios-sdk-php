<?php

use Correios\Client;

require 'vendor/autoload.php';

// echo "test";

$correios = new Client;

print_r($correios->zipcode()->find('05581-001'));