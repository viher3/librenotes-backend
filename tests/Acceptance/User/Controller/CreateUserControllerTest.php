<?php

namespace App\Tests\Acceptance\User\Controller;

use App\Tests\Acceptance\Security\Service\AdminLoginService;
use App\Tests\Acceptance\Security\Service\LoginService;
use App\Tests\Acceptance\User\Service\CreateUserService;
use App\Tests\Shared\Faker;
use App\User\Domain\ValueObject\UserId;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CreateUserControllerTest extends WebTestCase
{
    public function testCreateUserSuccess(): void
    {
        $client = static::createClient();
        $adminLogin = new AdminLoginService();
        $client = $adminLogin->execute($client);

        $createUserService = new CreateUserService();
        $client = $createUserService->execute($client, authorizationHeader: 'Bearer ' . $adminLogin->getAccessToken());

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $this->assertSame('[]', $client->getResponse()->getContent());
    }

    public function testCreateUserValidationError(): void
    {
        $client = static::createClient();
        $adminLogin = new AdminLoginService();
        $client = $adminLogin->execute($client);

        (new CreateUserService())->execute(
            client: $client,
            email: 'not-an-email',
            password: 'short',
            authorizationHeader: 'Bearer ' . $adminLogin->getAccessToken()
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateUserWithDuplicateEmailReturnsConflict(): void
    {
        $email = Faker::create()->email();
        $client = static::createClient();
        $adminLogin = new AdminLoginService();
        $client = $adminLogin->execute($client);
        $authorizationHeader = 'Bearer ' . $adminLogin->getAccessToken();

        (new CreateUserService())->execute($client, email: $email, authorizationHeader: $authorizationHeader);
        $client = (new CreateUserService())->execute(
            $client,
            id: UserId::random(),
            email: $email,
            authorizationHeader: $authorizationHeader
        );

        $this->assertResponseStatusCodeSame(409);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('user.email_already_exists', $data['code']);
    }

    public function testCreateUserRejectsUnauthenticatedRequest(): void
    {
        $client = static::createClient();

        (new CreateUserService())->execute($client);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateUserRejectsNonAdminUser(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);

        (new CreateUserService())->execute(
            $client,
            authorizationHeader: 'Bearer ' . $loginService->getAccessToken()
        );

        $this->assertResponseStatusCodeSame(403);
    }
}