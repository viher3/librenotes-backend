<?php

declare(strict_types=1);

namespace App\Tests\Unit\Configuration\Infrastructure\Console;

use App\Configuration\Domain\Model\Configuration;
use App\Configuration\Domain\Model\ConfigurationHistory;
use App\Configuration\Domain\Model\ValueObject\ConfigurationHistoryAction;
use App\Configuration\Domain\Model\ValueObject\ConfigurationHistoryId;
use App\Configuration\Domain\Model\ValueObject\ConfigurationId;
use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;
use App\Configuration\Domain\Model\ValueObject\ConfigurationType;
use App\Configuration\Domain\Model\ValueObject\ConfigurationValue;
use App\Configuration\Domain\Repository\ConfigurationHistoryRepository;
use App\Configuration\Domain\Repository\ConfigurationRepository;
use App\Configuration\Domain\Service\ConfigurationValueEncryptor;
use App\Configuration\Domain\Service\EncryptedValue;
use App\Configuration\Infrastructure\Cache\ConfigurationValueCache;
use App\Configuration\Infrastructure\Console\RotateConfigurationEncryptionKeyCommand;
use App\Core\Domain\ValueObject\BoolValueObject;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Core\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RotateConfigurationEncryptionKeyCommandTest extends TestCase
{
    private function configuration(int $keyVersion): Configuration
    {
        $now = new DateTimeValueObject(new \DateTimeImmutable());

        return new Configuration(
            id: ConfigurationId::random(),
            key: new ConfigurationKey('payments.stripe_secret_key'),
            value: new ConfigurationValue('old-cipher=='),
            type: new ConfigurationType(ConfigurationType::STRING),
            description: null,
            isSensitive: new BoolValueObject(true),
            keyVersion: $keyVersion,
            createdAt: $now,
            updatedAt: $now,
            changedBy: Uuid::random()->value(),
        );
    }

    public function testRotatesAConfigurationStillOnTheOldKeyVersion(): void
    {
        $configurationRepository = $this->createMock(ConfigurationRepository::class);
        $historyRepository = $this->createMock(ConfigurationHistoryRepository::class);
        $encryptor = $this->createMock(ConfigurationValueEncryptor::class);
        $cache = $this->createMock(ConfigurationValueCache::class);

        $configuration = $this->configuration(1);

        $configurationRepository->expects($this->exactly(2))
            ->method('findSensitiveNotOnKeyVersion')
            ->willReturnOnConsecutiveCalls([$configuration], []);

        $historyRepository->method('findSensitiveNotOnKeyVersion')->willReturn([]);

        $encryptor->method('currentKeyVersion')->willReturn(2);
        $encryptor->expects($this->once())->method('decrypt')->with('old-cipher==', 1)->willReturn('plaintext');
        $encryptor->expects($this->once())->method('encrypt')->with('plaintext')
            ->willReturn(new EncryptedValue('new-cipher==', 2));

        $configurationRepository->expects($this->once())
            ->method('rotateEncryptedValue')
            ->with($configuration->getId(), 'new-cipher==', 2);

        $cache->expects($this->once())->method('clear');

        $command = new RotateConfigurationEncryptionKeyCommand(
            $configurationRepository,
            $historyRepository,
            $encryptor,
            $cache,
        );

        $exitCode = (new CommandTester($command))->execute([]);

        self::assertSame(0, $exitCode);
    }

    /**
     * The case the design exists for: a history row whose previous_value and
     * new_value were encrypted under different key versions must have each side
     * rotated independently, not treated as a single unit.
     */
    public function testRotatesEachSideOfAHistoryRowIndependently(): void
    {
        $configurationRepository = $this->createMock(ConfigurationRepository::class);
        $historyRepository = $this->createMock(ConfigurationHistoryRepository::class);
        $encryptor = $this->createMock(ConfigurationValueEncryptor::class);
        $cache = $this->createMock(ConfigurationValueCache::class);

        $configurationRepository->method('findSensitiveNotOnKeyVersion')->willReturn([]);

        $now = new DateTimeValueObject(new \DateTimeImmutable());
        $entry = new ConfigurationHistory(
            id: ConfigurationHistoryId::random(),
            configurationId: ConfigurationId::random(),
            configurationKey: new ConfigurationKey('payments.stripe_secret_key'),
            action: new ConfigurationHistoryAction(ConfigurationHistoryAction::UPDATED),
            previousValue: new ConfigurationValue('previous-cipher-v1=='),
            previousValueKeyVersion: 1,
            newValue: new ConfigurationValue('new-cipher-v2=='),
            newValueKeyVersion: 2,
            changedBy: Uuid::random(),
            changedAt: $now,
        );

        $historyRepository->expects($this->exactly(2))
            ->method('findSensitiveNotOnKeyVersion')
            ->willReturnOnConsecutiveCalls([$entry], []);

        $encryptor->method('currentKeyVersion')->willReturn(2);

        // Only the v1 side needs work; the v2 side already matches the target.
        $encryptor->expects($this->once())->method('decrypt')
            ->with('previous-cipher-v1==', 1)
            ->willReturn('previous-plaintext');
        $encryptor->expects($this->once())->method('encrypt')
            ->with('previous-plaintext')
            ->willReturn(new EncryptedValue('previous-cipher-v2==', 2));

        $historyRepository->expects($this->once())
            ->method('rotateEncryptedValues')
            ->with(
                $entry->getId(),
                'previous-cipher-v2==',
                2,
                'new-cipher-v2==', // untouched: was already on the target version
                2,
            );

        $command = new RotateConfigurationEncryptionKeyCommand(
            $configurationRepository,
            $historyRepository,
            $encryptor,
            $cache,
        );

        (new CommandTester($command))->execute([]);
    }
}
