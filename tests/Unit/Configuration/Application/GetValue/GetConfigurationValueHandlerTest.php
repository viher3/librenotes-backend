<?php

declare(strict_types=1);

namespace App\Tests\Unit\Configuration\Application\GetValue;

use App\Configuration\Application\GetValue\GetConfigurationValueHandler;
use App\Configuration\Application\GetValue\GetConfigurationValueQuery;
use App\Configuration\Domain\Model\Configuration;
use App\Configuration\Domain\Model\ValueObject\ConfigurationId;
use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;
use App\Configuration\Domain\Model\ValueObject\ConfigurationType;
use App\Configuration\Domain\Model\ValueObject\ConfigurationValue;
use App\Configuration\Domain\Repository\ConfigurationRepository;
use App\Configuration\Domain\Service\ConfigurationValueCaster;
use App\Configuration\Domain\Service\ConfigurationValueEncryptor;
use App\Configuration\Infrastructure\Cache\ConfigurationValueCache;
use App\Core\Domain\ValueObject\BoolValueObject;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Core\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class GetConfigurationValueHandlerTest extends TestCase
{
    private ConfigurationRepository $repository;
    private ConfigurationValueEncryptor $encryptor;
    private ConfigurationValueCache $cache;
    private GetConfigurationValueHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ConfigurationRepository::class);
        $this->encryptor = $this->createMock(ConfigurationValueEncryptor::class);
        $this->cache = $this->createMock(ConfigurationValueCache::class);

        // Simulates a cache miss: forwards straight to the compute callback.
        $this->cache->method('get')->willReturnCallback(
            static fn (string $key, callable $compute) => $compute()
        );

        $this->handler = new GetConfigurationValueHandler(
            $this->repository,
            $this->encryptor,
            new ConfigurationValueCaster(),
            $this->cache,
        );
    }

    private function configuration(string $storedValue, string $type, bool $isSensitive, ?int $keyVersion): Configuration
    {
        $now = new DateTimeValueObject(new \DateTimeImmutable());

        return new Configuration(
            id: ConfigurationId::random(),
            key: new ConfigurationKey('feature.max_items'),
            value: new ConfigurationValue($storedValue),
            type: new ConfigurationType($type),
            description: null,
            isSensitive: new BoolValueObject($isSensitive),
            keyVersion: $keyVersion,
            createdAt: $now,
            updatedAt: $now,
            changedBy: Uuid::random()->value(),
        );
    }

    public function testReturnsNotFoundWhenKeyDoesNotExist(): void
    {
        $this->repository->method('findByKey')->willReturn(null);

        $response = $this->handler->__invoke(new GetConfigurationValueQuery('unknown.key'));

        self::assertFalse($response->found());
        self::assertNull($response->value());
    }

    public function testReturnsTheTypedValueForANonSensitiveConfiguration(): void
    {
        $this->repository->method('findByKey')->willReturn(
            $this->configuration('42', ConfigurationType::INTEGER, false, null)
        );
        $this->encryptor->expects($this->never())->method('decrypt');

        $response = $this->handler->__invoke(new GetConfigurationValueQuery('feature.max_items'));

        self::assertTrue($response->found());
        self::assertSame(42, $response->value());
    }

    public function testDecryptsBeforeCastingForASensitiveConfiguration(): void
    {
        $this->repository->method('findByKey')->willReturn(
            $this->configuration('cipher==', ConfigurationType::STRING, true, 2)
        );
        $this->encryptor->expects($this->once())
            ->method('decrypt')
            ->with('cipher==', 2)
            ->willReturn('sk_live_secret');

        $response = $this->handler->__invoke(new GetConfigurationValueQuery('feature.max_items'));

        self::assertTrue($response->found());
        self::assertSame('sk_live_secret', $response->value());
    }
}
