<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Configuration\Controller;

use App\Tests\Acceptance\Configuration\Service\CreateConfigurationService;
use App\Tests\Acceptance\Security\Service\LoginService;
use App\Tests\Shared\Faker;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConfigurationDetailControllerTest extends WebTestCase
{
    public function testDetailReturnsTheValueForANonSensitiveConfiguration(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);
        $key = 'mail.' . Faker::create()->word();

        (new CreateConfigurationService())->execute($client, $loginService->getAccessToken(), key: $key, value: 'Acme Support');

        $client->request('GET', '/configurations/' . $key, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $loginService->getAccessToken(),
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Acme Support', $data['value']);
        $this->assertFalse($data['is_sensitive']);
    }

    public function testDetailNeverReturnsTheValueForASensitiveConfiguration(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);
        $key = 'payments.' . Faker::create()->word();

        (new CreateConfigurationService())->execute(
            $client,
            $loginService->getAccessToken(),
            key: $key,
            value: 'sk_live_super_secret',
            isSensitive: true,
        );

        $client->request('GET', '/configurations/' . $key, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $loginService->getAccessToken(),
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['is_sensitive']);
        $this->assertNull($data['value']);
        $this->assertStringNotContainsString('sk_live_super_secret', $client->getResponse()->getContent());
    }

    public function testHistoryNeverReturnsValuesForASensitiveConfiguration(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);
        $key = 'payments.' . Faker::create()->word();

        (new CreateConfigurationService())->execute(
            $client,
            $loginService->getAccessToken(),
            key: $key,
            value: 'sk_live_super_secret',
            isSensitive: true,
        );

        $client->request('GET', '/configurations/' . $key . '/history', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $loginService->getAccessToken(),
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('sk_live_super_secret', $client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('created', $data['data'][0]['action']);
        $this->assertNull($data['data'][0]['new_value']);
    }

    public function testDetailReturns404ForAnUnknownKey(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);

        $client->request('GET', '/configurations/does.not.exist', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $loginService->getAccessToken(),
        ]);

        $this->assertResponseStatusCodeSame(404);
    }
}
