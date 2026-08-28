<?php

namespace App\Tests\Acceptance\Security\Controller;

use App\Tests\Acceptance\Security\Service\LoginService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LogoutControllerTest extends WebTestCase
{
    public function testLogoutRevokesTheRefreshToken(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);

        $client->request(
            'DELETE',
            '/token-renew',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => $loginService->getRefreshToken()])
        );

        $this->assertResponseIsSuccessful();

        $client->request(
            'PUT',
            '/token-renew',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => $loginService->getRefreshToken()])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLogoutIsIdempotentForAnUnknownToken(): void
    {
        $client = static::createClient();

        $client->request(
            'DELETE',
            '/token-renew',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => 'not-a-real-token'])
        );

        $this->assertResponseIsSuccessful();
    }
}
