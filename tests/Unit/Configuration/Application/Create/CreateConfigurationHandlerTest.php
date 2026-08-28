<?php

declare(strict_types=1);

namespace App\Tests\Unit\Configuration\Application\Create;

use App\Configuration\Application\Create\CreateConfigurationCommand;
use App\Configuration\Application\Create\CreateConfigurationHandler;
use App\Configuration\Domain\Exception\ConfigurationKeyAlreadyExistsException;
use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;
use App\Configuration\Domain\Repository\ConfigurationRepository;
use App\Configuration\Domain\Service\ConfigurationValueCaster;
use App\Configuration\Domain\Service\ConfigurationValueEncryptor;
use App\Configuration\Domain\Service\EncryptedValue;
use App\Configuration\Domain\Specification\UniqueConfigurationKeySpecificationInterface;
use App\Core\Domain\Bus\Event\EventBus;
use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class CreateConfigurationHandlerTest extends TestCase
{
    private ConfigurationRepository $repository;
    private UniqueConfigurationKeySpecificationInterface $uniqueSpecification;
    private ConfigurationValueCaster $caster;
    private ConfigurationValueEncryptor $encryptor;
    private EventBus $eventBus;
    private DateTimeProvider $dateTimeProvider;
    private CreateConfigurationHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ConfigurationRepository::class);
        $this->uniqueSpecification = $this->createMock(UniqueConfigurationKeySpecificationInterface::class);
        $this->caster = new ConfigurationValueCaster();
        $this->encryptor = $this->createMock(ConfigurationValueEncryptor::class);
        $this->eventBus = $this->createMock(EventBus::class);
        $this->dateTimeProvider = $this->createMock(DateTimeProvider::class);
        $this->dateTimeProvider->method('now')->willReturn(new \DateTimeImmutable('2026-08-19 10:00:00'));

        $this->handler = new CreateConfigurationHandler(
            $this->repository,
            $this->uniqueSpecification,
            $this->caster,
            $this->encryptor,
            $this->eventBus,
            $this->dateTimeProvider,
        );
    }

    public function testNonSensitiveValueIsStoredInPlaintext(): void
    {
        $this->encryptor->expects($this->never())->method('encrypt');

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($configuration) {
                self::assertSame('mail.sender_name', $configuration->getKey()->value());
                self::assertSame('Acme', $configuration->getValue()->value());
                self::assertNull($configuration->getKeyVersion());

                return true;
            }));

        $this->eventBus->expects($this->once())->method('publish');

        $this->handler->__invoke(new CreateConfigurationCommand(
            id: Uuid::random()->value(),
            key: 'mail.sender_name',
            value: 'Acme',
            type: 'string',
            description: null,
            isSensitive: false,
            changedBy: Uuid::random()->value(),
        ));
    }

    public function testSensitiveValueIsEncryptedBeforeStorage(): void
    {
        $this->encryptor->expects($this->once())
            ->method('encrypt')
            ->with('sk_live_secret')
            ->willReturn(new EncryptedValue('ciphertext==', 3));

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($configuration) {
                self::assertSame('ciphertext==', $configuration->getValue()->value());
                self::assertSame(3, $configuration->getKeyVersion());
                self::assertTrue($configuration->isSensitive()->value());

                return true;
            }));

        $this->handler->__invoke(new CreateConfigurationCommand(
            id: Uuid::random()->value(),
            key: 'payments.stripe_secret_key',
            value: 'sk_live_secret',
            type: 'string',
            description: null,
            isSensitive: true,
            changedBy: Uuid::random()->value(),
        ));
    }

    public function testRejectsAKeyThatAlreadyExists(): void
    {
        $this->uniqueSpecification->method('isUnique')
            ->willThrowException(new ConfigurationKeyAlreadyExistsException('mail.sender_name'));

        $this->repository->expects($this->never())->method('save');

        $this->expectException(ConfigurationKeyAlreadyExistsException::class);

        $this->handler->__invoke(new CreateConfigurationCommand(
            id: Uuid::random()->value(),
            key: 'mail.sender_name',
            value: 'Acme',
            type: 'string',
            description: null,
            isSensitive: false,
            changedBy: Uuid::random()->value(),
        ));
    }

    public function testUniquenessIsCheckedBeforeEncryptingOrSaving(): void
    {
        $this->uniqueSpecification->expects($this->once())
            ->method('isUnique')
            ->with($this->isInstanceOf(ConfigurationKey::class));

        $this->handler->__invoke(new CreateConfigurationCommand(
            id: Uuid::random()->value(),
            key: 'mail.sender_name',
            value: 'Acme',
            type: 'string',
            description: null,
            isSensitive: false,
            changedBy: Uuid::random()->value(),
        ));
    }
}
