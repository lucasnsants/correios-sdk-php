<?php

namespace Correios;

use Mockery;
use GuzzleHttp\ClientInterface;
use Correios\FreightInterface;

class ClientTest extends TestCase
{
    /**
     * @var \Correios\Client
     */
    protected $correios;

    public function setUp()
    {
        parent::setUp();

        $this->correios = new Client(
            Mockery::mock(ClientInterface::class),
            Mockery::mock(FreightInterface::class)
        );
    }

    public function testFreightService()
    {
        $this->assertInstanceOf(FreightInterface::class, $this->correios->freight());
    }
}
