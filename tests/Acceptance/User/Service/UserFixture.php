<?php

namespace App\Tests\Acceptance\User\Service;

use App\Core\Infrastructure\Bus\Command\SymfonyTransactionalCommandBus;
use App\Tests\Shared\Faker;
use App\User\Application\Create\CreateUserCommand;
use App\User\Domain\ValueObject\UserId;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Creates an active, login-able user directly through the command bus, bypassing
 * POST /users entirely. Test setup (login, other modules' fixtures) must not depend
 * on that endpoint's own authorization — it is now ROLE_ADMIN-gated and tested in
 * its own right by CreateUserControllerTest.
 */
class UserFixture
{
    protected string $id;
    protected string $email;
    protected string $password;
    protected string $username;

    public function create(
        KernelBrowser $client,
        ?string $id = null,
        ?string $email = null,
        ?string $password = null,
        ?string $username = null
    ): self {
        $this->id = $id ?? UserId::random()->value();
        $this->email = $email ?? Faker::create()->email();
        $this->password = $password ?? Faker::create()->password(8, 16);
        $this->username = $username ?? Faker::create()->userName();

        $client->getContainer()->get(SymfonyTransactionalCommandBus::class)->__invoke(
            new CreateUserCommand(
                id: $this->id,
                email: $this->email,
                password: $this->password,
                username: $this->username,
                isActive: true,
                isEmailValidated: false
            )
        );

        return $this;
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
