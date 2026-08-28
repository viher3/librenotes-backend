<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Configuration\Controller;

use App\Tests\Acceptance\Configuration\Service\CreateConfigurationService;
use App\Tests\Acceptance\Security\Service\LoginService;
use App\Tests\Shared\Faker;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CreateConfigurationControllerTest extends WebTestCase
{
    public function testCreateConfigurationSuccess(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);

        (new CreateConfigurationService())->execute(
            $client,
            $loginService->getAccessToken(),
            key: 'mail.' . Faker::create()->word(),
            value: 'Acme Support',
        );

        $this->assertResponseStatusCodeSame(201);
    }

    public function testCreateConfigurationRejectsUnknownType(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);

        (new CreateConfigurationService())->execute(
            $client,
            $loginService->getAccessToken(),
            key: 'mail.' . Faker::create()->word(),
            value: 'Acme Support',
            type: 'not-a-real-type',
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateConfigurationRejectsDuplicateKey(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);
        $key = 'mail.' . Faker::create()->word();

        (new CreateConfigurationService())->execute($client, $loginService->getAccessToken(), key: $key, value: 'first');
        (new CreateConfigurationService())->execute($client, $loginService->getAccessToken(), key: $key, value: 'second');

        $this->assertResponseStatusCodeSame(409);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('configuration.key_already_exists', $data['code']);
    }

    public function testCreateConfigurationRejectsAValueThatDoesNotMatchTheDeclaredType(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);

        (new CreateConfigurationService())->execute(
            $client,
            $loginService->getAccessToken(),
            key: 'feature.' . Faker::create()->word(),
            value: 'not-an-integer',
            type: 'integer',
        );

        $this->assertResponseStatusCodeSame(422);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('configuration.invalid_value', $data['code']);
    }

    public function testCreateConfigurationWithoutATokenIsRejected(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/configurations',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['key' => 'mail.sender_name', 'value' => 'Acme', 'type' => 'string'])
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
