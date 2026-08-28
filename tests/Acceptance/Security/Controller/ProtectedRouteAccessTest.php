<?php

namespace App\Tests\Acceptance\Security\Controller;

use App\Tests\Acceptance\Security\Service\LoginService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProtectedRouteAccessTest extends WebTestCase
{
    public function testProtectedRouteRejectsRequestWithoutToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/users');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testProtectedRouteRejectsGarbageToken(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/users',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer not-a-real-token']
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testProtectedRouteAcceptsAValidToken(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);

        $client->request(
            'GET',
            '/users',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $loginService->getAccessToken()]
        );

        $this->assertResponseIsSuccessful();
    }

    public function testPublicRoutesStayAccessibleWithoutAToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/api/docs');
        $this->assertResponseIsSuccessful();
    }
}
