<?php

namespace App\Tests\Acceptance\Security\Controller;

use App\Tests\Acceptance\User\Service\UserFixture;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RenewUserAuthenticationControllerTest extends WebTestCase
{
    public function testRenewUserAuthenticationSuccess(): void
    {
        $client = static::createClient();
        $refreshToken = $this->loginAndGetRefreshToken($client);

        $client->request(
            'PUT',
            '/token-renew',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => $refreshToken])
        );

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('accessToken', $responseData);
        $this->assertArrayHasKey('refreshToken', $responseData);
        $this->assertNotSame($refreshToken, $responseData['refreshToken']);
    }

    public function testRenewUserAuthenticationRejectsReusedToken(): void
    {
        $client = static::createClient();
        $refreshToken = $this->loginAndGetRefreshToken($client);

        $client->request(
            'PUT',
            '/token-renew',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => $refreshToken])
        );
        $this->assertResponseIsSuccessful();

        $client->request(
            'PUT',
            '/token-renew',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => $refreshToken])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testRenewUserAuthenticationRejectsUnknownToken(): void
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/token-renew',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => 'not-a-real-token'])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    private function loginAndGetRefreshToken($client): string
    {
        $userFixture = new UserFixture();
        $userFixture->create($client);

        $client->request(
            'POST',
            '/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $userFixture->getEmail(),
                'password' => $userFixture->getPassword()
            ])
        );

        $loginData = json_decode($client->getResponse()->getContent(), true);

        return $loginData['refreshToken'];
    }
}
