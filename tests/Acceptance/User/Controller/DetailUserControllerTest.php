<?php

namespace App\Tests\Acceptance\User\Controller;

use App\Tests\Acceptance\Security\Service\LoginService;
use App\User\Domain\ValueObject\UserId;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DetailUserControllerTest extends WebTestCase
{
    public function testDetailUserSuccess(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);

        $client->request(
            'GET',
            '/users/' . $loginService->getUserId(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $loginService->getAccessToken(),
            ],
        );

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $this->assertEquals($loginService->getEmail(), json_decode($client->getResponse()->getContent(), true)['email']);
        $this->assertEquals($loginService->getUsername(), json_decode($client->getResponse()->getContent(), true)['username']);
    }

    public function testDetailUserNotFound(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);

        $client->request(
            'GET',
            '/users/' . UserId::random(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $loginService->getAccessToken(),
            ],
        );

        $this->assertResponseStatusCodeSame(404);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('user.not_found', $data['code']);
    }
}
