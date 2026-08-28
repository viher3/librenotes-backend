<?php

namespace App\Tests\Acceptance\User\Controller;

use App\Tests\Shared\Faker;
use App\User\Domain\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SignUpControllerTest extends WebTestCase
{
    public function testSignUpCreatesAnInactiveUnvalidatedUser(): void
    {
        $client = static::createClient();
        $email = Faker::create()->email();

        $client->request(
            'POST',
            '/signup',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => Faker::create()->password(8, 16),
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $user = $client->getContainer()->get(UserRepository::class)->findByEmail($email);
        $this->assertNotNull($user);
        $this->assertFalse($user->isActive());
        $this->assertFalse($user->isEmailValidated());
        $this->assertNotNull($user->getActivationToken());
    }

    public function testSignUpValidationError(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/signup',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'not-an-email', 'password' => 'short'])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testSignUpWithDuplicateEmailReturnsConflict(): void
    {
        $client = static::createClient();
        $email = Faker::create()->email();
        $body = json_encode(['email' => $email, 'password' => Faker::create()->password(8, 16)]);

        $client->request('POST', '/signup', [], [], ['CONTENT_TYPE' => 'application/json'], $body);
        $client->request('POST', '/signup', [], [], ['CONTENT_TYPE' => 'application/json'], $body);

        $this->assertResponseStatusCodeSame(409);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('user.email_already_exists', $data['code']);
    }

    public function testSignedUpUserCannotLogInBeforeActivation(): void
    {
        $client = static::createClient();
        $email = Faker::create()->email();
        $password = Faker::create()->password(8, 16);

        $client->request(
            'POST',
            '/signup',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password])
        );
        $this->assertResponseStatusCodeSame(201);

        $client->request(
            'POST',
            '/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password])
        );

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('security.inactive_user', $data['code']);
    }
}
