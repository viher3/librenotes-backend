<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Notification\Controller;

use App\Tests\Acceptance\Security\Service\LoginService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NotificationSearchControllerTest extends WebTestCase
{
    public function testSearchReturnsPaginatedEnvelope(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);

        $client->request('GET', '/notifications', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $loginService->getAccessToken(),
        ]);

        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('page', $data['meta']);
        $this->assertArrayHasKey('total', $data['meta']);
    }

    public function testSearchWithoutATokenIsRejected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/notifications');

        $this->assertResponseStatusCodeSame(401);
    }
}
