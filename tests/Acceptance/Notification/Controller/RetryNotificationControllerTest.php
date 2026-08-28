<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Notification\Controller;

use App\Core\Infrastructure\Bus\Command\SymfonyTransactionalCommandBus;
use App\Notification\Application\Send\SendNotificationCommand;
use App\Tests\Acceptance\Notification\Service\CreateNotificationTemplateService;
use App\Tests\Acceptance\Security\Service\LoginService;
use App\Tests\Shared\Faker;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * There is no POST /notifications endpoint by design (Send is bus-only, dispatched by
 * other modules — see the module's design doc). To exercise retry against a real
 * notification, these tests seed one by dispatching SendNotificationCommand directly
 * through the container's transactional command bus, exactly like an internal caller
 * would, then drive the actual HTTP endpoints under test.
 */
class RetryNotificationControllerTest extends WebTestCase
{
    public function testRetryOfUnknownNotificationReturnsNotFound(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);

        $client->request('POST', '/notifications/' . Uuid::uuid4()->toString() . '/retry', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $loginService->getAccessToken(),
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testRetryOfASentNotificationIsRejected(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);

        $templateKey = 'user.' . Faker::create()->word();
        (new CreateNotificationTemplateService())->execute($client, $loginService->getAccessToken(), key: $templateKey);
        $this->assertResponseStatusCodeSame(201);

        $notificationId = Uuid::uuid4()->toString();

        $commandBus = static::getContainer()->get(SymfonyTransactionalCommandBus::class);
        $commandBus->__invoke(new SendNotificationCommand(
            id: $notificationId,
            channel: 'email',
            templateKey: $templateKey,
            recipient: 'user@example.com',
            payload: ['name' => 'Alice'],
        ));

        $client->request('GET', '/notifications/' . $notificationId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $loginService->getAccessToken(),
        ]);
        $detail = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('sent', $detail['status']);

        $client->request('POST', '/notifications/' . $notificationId . '/retry', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $loginService->getAccessToken(),
        ]);

        $this->assertResponseStatusCodeSame(409);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('notification.not_retryable', $data['code']);
    }

    public function testRetryOfAFailedNotificationSucceeds(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);

        $notificationId = Uuid::uuid4()->toString();

        $commandBus = static::getContainer()->get(SymfonyTransactionalCommandBus::class);
        $commandBus->__invoke(new SendNotificationCommand(
            id: $notificationId,
            channel: 'email',
            templateKey: 'user.missing_' . bin2hex(random_bytes(4)),
            recipient: 'user@example.com',
        ));

        $client->request('GET', '/notifications/' . $notificationId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $loginService->getAccessToken(),
        ]);
        $detail = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('failed', $detail['status']);

        $client->request('POST', '/notifications/' . $notificationId . '/retry', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $loginService->getAccessToken(),
        ]);

        $this->assertResponseStatusCodeSame(200);
    }
}
