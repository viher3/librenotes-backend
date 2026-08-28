<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Notification\Service;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class CreateNotificationTemplateService
{
    public function execute(
        KernelBrowser $client,
        string $accessToken,
        string $key,
        string $channel = 'email',
        string $locale = 'en',
        string $subject = 'Subject {{ name }}',
        string $body = 'Body {{ name }}',
        bool $isActive = true
    ): KernelBrowser {
        $client->request(
            'POST',
            '/notification-templates',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $accessToken,
            ],
            json_encode([
                'key' => $key,
                'channel' => $channel,
                'locale' => $locale,
                'subject' => $subject,
                'body' => $body,
                'is_active' => $isActive,
            ])
        );

        return $client;
    }
}
