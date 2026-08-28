<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Configuration\Service;

use App\Configuration\Domain\Model\ValueObject\ConfigurationId;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class CreateConfigurationService
{
    public function execute(
        KernelBrowser $client,
        string $accessToken,
        string $key,
        string $value,
        string $type = 'string',
        ?string $description = null,
        bool $isSensitive = false
    ): KernelBrowser {
        $client->request(
            'POST',
            '/configurations',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $accessToken,
            ],
            json_encode([
                'id' => ConfigurationId::random()->value(),
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'description' => $description,
                'is_sensitive' => $isSensitive,
            ])
        );

        return $client;
    }
}
