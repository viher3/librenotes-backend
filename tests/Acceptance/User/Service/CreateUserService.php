<?php

namespace App\Tests\Acceptance\User\Service;

use App\Tests\Shared\Faker;
use App\User\Domain\ValueObject\UserId;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class CreateUserService
{
    protected string $id;
    protected string $email;
    protected string $password;
    protected string $username;

    public function execute(
        KernelBrowser $client,
        string $id = null,
        string $email = null,
        string $password = null,
        string $username = null,
        ?string $authorizationHeader = null
    ) : KernelBrowser
    {
        $this->id = $id ?? UserId::random();
        $this->email = $email ?? Faker::create()->email();
        $this->password = $password ?? Faker::create()->password(8,16);
        $this->username = $username ?? Faker::create()->userName();

        $server = ['CONTENT_TYPE' => 'application/json'];

        if ($authorizationHeader !== null) {
            $server['HTTP_AUTHORIZATION'] = $authorizationHeader;
        }

        $client->request(
            'POST',
            '/users',
            [],
            [],
            $server,
            json_encode([
                'id' => $this->id,
                'email' => $this->email,
                'password' => $this->password,
                'username' => $this->username
            ])
        );

        return $client;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getUsername(): string
    {
        return $this->username;
    }
}
