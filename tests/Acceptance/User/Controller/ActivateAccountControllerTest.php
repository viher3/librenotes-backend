<?php

namespace App\Tests\Acceptance\User\Controller;

use App\Tests\Shared\Faker;
use App\User\Domain\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ActivateAccountControllerTest extends WebTestCase
{
    public function testActivatesAccountWithAValidTokenAndAllowsLoginAfterwards(): void
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

        $userRepository = $client->getContainer()->get(UserRepository::class);
        $token = $userRepository->findByEmail($email)->getActivationToken()->value();

        $client->request(
            'POST',
            '/users/activate',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => $token])
        );
        $this->assertResponseIsSuccessful();

        $activatedUser = $userRepository->findByEmail($email);
        $this->assertTrue($activatedUser->isActive());
        $this->assertTrue($activatedUser->isEmailValidated());
        $this->assertNull($activatedUser->getActivationToken());

        $client->request(
            'POST',
            '/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password])
        );
        $this->assertResponseIsSuccessful();
    }

    public function testRejectsAnUnknownToken(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/users/activate',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => 'not-a-real-token'])
        );

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('user.invalid_activation_token', $data['code']);
    }
}
