<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Notification\Controller;

use App\Tests\Acceptance\Notification\Service\CreateNotificationTemplateService;
use App\Tests\Acceptance\Security\Service\LoginService;
use App\Tests\Shared\Faker;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CreateNotificationTemplateControllerTest extends WebTestCase
{
    public function testCreateTemplateSuccess(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);

        (new CreateNotificationTemplateService())->execute(
            $client,
            $loginService->getAccessToken(),
            key: 'user.' . Faker::create()->word(),
        );

        $this->assertResponseStatusCodeSame(201);
    }

    public function testCreateTemplateRejectsDuplicateKeyChannelLocale(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);
        $key = 'user.' . Faker::create()->word();

        (new CreateNotificationTemplateService())->execute($client, $loginService->getAccessToken(), key: $key);
        (new CreateNotificationTemplateService())->execute($client, $loginService->getAccessToken(), key: $key);

        $this->assertResponseStatusCodeSame(409);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('notification.template_already_exists', $data['code']);
    }

    public function testCreateTemplateRejectsInvalidKeyFormat(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);

        (new CreateNotificationTemplateService())->execute(
            $client,
            $loginService->getAccessToken(),
            key: 'Not A Valid Key!',
        );

        $this->assertResponseStatusCodeSame(422);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('notification.invalid_template_key_format', $data['code']);
    }

    public function testCreateTemplateWithoutATokenIsRejected(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/notification-templates',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'key' => 'user.welcome',
                'channel' => 'email',
                'locale' => 'en',
                'subject' => 'Hi',
                'body' => 'Hi',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
