<?php

namespace App\Tests\Acceptance\Security\Service;

use App\Tests\Acceptance\User\Service\UserFixture;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class LoginService
{
    private UserFixture $userFixture;
    private string $accessToken;
    private string $refreshToken;

    public function execute(KernelBrowser $client) : KernelBrowser
    {
        $this->userFixture = new UserFixture();
        $this->userFixture->create($client);

        $client->request(
            'POST',
            '/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $this->userFixture->getEmail(),
                'password' => $this->userFixture->getPassword()
            ])
        );

        $loginData = json_decode($client->getResponse()->getContent(), true);
        $this->accessToken = $loginData['accessToken'];
        $this->refreshToken = $loginData['refreshToken'];

        return $client;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function getUserId(): string
    {
        return $this->userFixture->getId();
    }

    public function getEmail(): string
    {
        return $this->userFixture->getEmail();
    }

    public function getUsername(): string
    {
        return $this->userFixture->getUsername();
    }
}
