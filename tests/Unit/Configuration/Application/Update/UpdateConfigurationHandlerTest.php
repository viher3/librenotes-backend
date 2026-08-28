<?php

declare(strict_types=1);

namespace App\Tests\Unit\Configuration\Application\Update;

use App\Configuration\Application\Update\UpdateConfigurationCommand;
use App\Configuration\Application\Update\UpdateConfigurationHandler;
use App\Configuration\Domain\Exception\ConfigurationNotFoundException;
use App\Configuration\Domain\Model\Configuration;
use App\Configuration\Domain\Model\ValueObject\ConfigurationId;
use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;
use App\Configuration\Domain\Model\ValueObject\ConfigurationType;
use App\Configuration\Domain\Model\ValueObject\ConfigurationValue;
use App\Configuration\Domain\Repository\ConfigurationRepository;
use App\Configuration\Domain\Service\ConfigurationValueCaster;
use App\Configuration\Domain\Service\ConfigurationValueEncryptor;
use App\Configuration\Domain\Service\EncryptedValue;
use App\Core\Domain\Bus\Event\EventBus;
use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\BoolValueObject;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Core\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class UpdateConfigurationHandlerTest extends TestCase
{
    private ConfigurationRepository $repository;
    private ConfigurationValueCaster $caster;
    private ConfigurationValueEncryptor $encryptor;
    private EventBus $eventBus;
    private UpdateConfigurationHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ConfigurationRepository::class);
        $this->caster = new ConfigurationValueCaster();
        $this->encryptor = $this->createMock(ConfigurationValueEncryptor::class);
        $this->eventBus = $this->createMock(EventBus::class);

        $dateTimeProvider = $this->createMock(DateTimeProvider::class);
        $dateTimeProvider->method('now')->willReturn(new \DateTimeImmutable('2026-08-19 10:00:00'));

        $this->handler = new UpdateConfigurationHandler(
            $this->repository,
            $this->caster,
            $this->encryptor,
            $this->eventBus,
            $dateTimeProvider,
        );
    }

    private function existingConfiguration(string $storedValue, bool $isSensitive, ?int $keyVersion): Configuration
    {
        $now = new DateTimeValueObject(new \DateTimeImmutable('2026-08-01 00:00:00'));

        return new Configuration(
            id: ConfigurationId::random(),
            key: new ConfigurationKey('mail.sender_name'),
            value: new ConfigurationValue($storedValue),
            type: new ConfigurationType(ConfigurationType::STRING),
            description: null,
            isSensitive: new BoolValueObject($isSensitive),
            keyVersion: $keyVersion,
            createdAt: $now,
            updatedAt: $now,
            changedBy: Uuid::random()->value(),
        );
    }

    public function testThrowsWhenKeyDoesNotExist(): void
    {
        $this->repository->method('findByKey')->willReturn(null);

        $this->expectException(ConfigurationNotFoundException::class);

        $this->handler->__invoke(new UpdateConfigurationCommand(
            key: 'unknown.key',
            value: 'new value',
            type: null,
            description: null,
            isSensitive: null,
            changedBy: Uuid::random()->value(),
        ));
    }

    public function testUpdatingAPlainValueNeverTouchesTheEncryptor(): void
    {
        $this->repository->method('findByKey')->willReturn($this->existingConfiguration('Acme', false, null));
        $this->encryptor->expects($this->never())->method('decrypt');
        $this->encryptor->expects($this->never())->method('encrypt');

        $this->handler->__invoke(new UpdateConfigurationCommand(
            key: 'mail.sender_name',
            value: 'Acme Support',
            type: null,
            description: null,
            isSensitive: null,
            changedBy: Uuid::random()->value(),
        ));
    }

    /**
     * The tricky path: no new value supplied, but is_sensitive flips false -> true.
     * The handler must read the current plaintext (no decrypt needed, it wasn't
     * encrypted yet) and re-store it encrypted under the current key.
     */
    public function testTogglingSensitiveOnWithoutANewValueEncryptsTheExistingPlaintext(): void
    {
        $configuration = $this->existingConfiguration('Acme Support', false, null);
        $this->repository->method('findByKey')->willReturn($configuration);

        $this->encryptor->expects($this->never())->method('decrypt');
        $this->encryptor->expects($this->once())
            ->method('encrypt')
            ->with('Acme Support')
            ->willReturn(new EncryptedValue('cipher==', 1));

        $this->handler->__invoke(new UpdateConfigurationCommand(
            key: 'mail.sender_name',
            value: null,
            type: null,
            description: null,
            isSensitive: true,
            changedBy: Uuid::random()->value(),
        ));

        self::assertSame('cipher==', $configuration->getValue()->value());
        self::assertSame(1, $configuration->getKeyVersion());
    }

    /**
     * The inverse: value already encrypted, no new value supplied, is_sensitive
     * flips true -> false. The handler must decrypt the existing ciphertext first,
     * then store the plaintext.
     */
    public function testTogglingSensitiveOffWithoutANewValueDecryptsTheExistingCiphertext(): void
    {
        $configuration = $this->existingConfiguration('cipher==', true, 1);
        $this->repository->method('findByKey')->willReturn($configuration);

        $this->encryptor->expects($this->once())
            ->method('decrypt')
            ->with('cipher==', 1)
            ->willReturn('Acme Support');
        $this->encryptor->expects($this->never())->method('encrypt');

        $this->handler->__invoke(new UpdateConfigurationCommand(
            key: 'mail.sender_name',
            value: null,
            type: null,
            description: null,
            isSensitive: false,
            changedBy: Uuid::random()->value(),
        ));

        self::assertSame('Acme Support', $configuration->getValue()->value());
        self::assertNull($configuration->getKeyVersion());
    }

    public function testUpdatingAnAlreadySensitiveValueReencryptsTheNewPlaintext(): void
    {
        $configuration = $this->existingConfiguration('old-cipher==', true, 1);
        $this->repository->method('findByKey')->willReturn($configuration);

        $this->encryptor->expects($this->never())->method('decrypt');
        $this->encryptor->expects($this->once())
            ->method('encrypt')
            ->with('new-secret')
            ->willReturn(new EncryptedValue('new-cipher==', 1));

        $this->handler->__invoke(new UpdateConfigurationCommand(
            key: 'mail.sender_name',
            value: 'new-secret',
            type: null,
            description: null,
            isSensitive: null,
            changedBy: Uuid::random()->value(),
        ));

        self::assertSame('new-cipher==', $configuration->getValue()->value());
    }
}
