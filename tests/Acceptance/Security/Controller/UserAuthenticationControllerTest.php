<?php

namespace App\Tests\Acceptance\Security\Controller;

use App\Tests\Acceptance\User\Service\UserFixture;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserAuthenticationControllerTest extends WebTestCase
{
    public function testUserAuthenticationSuccess(): void
    {
        $client = static::createClient();
        $userFixture = new UserFixture();
        $userFixture->create($client);

        $email = $userFixture->getEmail();
        $password = $userFixture->getPassword();
        $client->request(
            'POST',
            '/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => $password
            ])
        );

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('accessToken', $responseData);
        $this->assertArrayHasKey('refreshToken', $responseData);
    }

    public function testUserAuthenticationValidationError(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'not-an-email',
                'password' => 'short'
            ])
        );

        $this->assertResponseStatusCodeSame(400);
    }
}

