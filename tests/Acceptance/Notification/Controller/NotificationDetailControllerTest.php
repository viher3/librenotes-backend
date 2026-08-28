<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Notification\Controller;

use App\Tests\Acceptance\Security\Service\LoginService;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NotificationDetailControllerTest extends WebTestCase
{
    public function testDetailOfUnknownNotificationReturnsNotFound(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);

        $client->request('GET', '/notifications/' . Uuid::uuid4()->toString(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $loginService->getAccessToken(),
        ]);

        $this->assertResponseStatusCodeSame(404);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('notification.not_found', $data['code']);
    }
}
