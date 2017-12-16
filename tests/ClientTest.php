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
            Mockery::mock(FreightInterface::class),
            Mockery::mock(ZipCodeInterface::class)
        );
    }

    public function testFreightService()
    {
        $this->assertInstanceOf(FreightInterface::class, $this->correios->freight());
    }

    public function testZipCodeService()
    {
        $this->assertInstanceOf(ZipCodeInterface::class, $this->correios->zipcode());
    }
}
