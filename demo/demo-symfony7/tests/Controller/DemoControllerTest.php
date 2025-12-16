<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DemoControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('SEPA Payment Bundle Demo', $client->getResponse()->getContent());
    }

    public function testValidateIban(): void
    {
        $client = static::createClient();
        $client->request('GET', '/validate-iban?iban=ES9121000418450200051332');

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['isValid']);
        $this->assertEquals('ES', $data['countryCode']);
    }

    public function testDemoMandate(): void
    {
        $client = static::createClient();
        $client->request('GET', '/demo-mandate');

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('MANDATE-001', $data['mandateId']);
        $this->assertTrue($data['active']);
    }

    public function testDemoRemesaPago(): void
    {
        $client = static::createClient();
        $client->request('GET', '/demo-remesa-pago');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('<?xml', $client->getResponse()->getContent());
        $this->assertStringContainsString('CstmrCdtTrfInitn', $client->getResponse()->getContent());
    }

    public function testDemoRemesaCobro(): void
    {
        $client = static::createClient();
        $client->request('GET', '/demo-remesa-cobro');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('<?xml', $client->getResponse()->getContent());
    }
}

