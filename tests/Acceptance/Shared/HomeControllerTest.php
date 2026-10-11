<?php

namespace App\Tests\Acceptance\Shared;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testHomeExposesRegistrationEnabledFlag(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsBool($data['registration_enabled']);
    }
}
