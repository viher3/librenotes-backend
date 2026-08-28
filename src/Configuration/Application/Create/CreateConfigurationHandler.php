<?php

declare(strict_types=1);

namespace App\Configuration\Application\Create;

use App\Configuration\Domain\Model\Configuration;
use App\Configuration\Domain\Model\ValueObject\ConfigurationDescription;
use App\Configuration\Domain\Model\ValueObject\ConfigurationId;
use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;
use App\Configuration\Domain\Model\ValueObject\ConfigurationType;
use App\Configuration\Domain\Model\ValueObject\ConfigurationValue;
use App\Configuration\Domain\Repository\ConfigurationRepository;
use App\Configuration\Domain\Service\ConfigurationValueCaster;
use App\Configuration\Domain\Service\ConfigurationValueEncryptor;
use App\Configuration\Domain\Specification\UniqueConfigurationKeySpecificationInterface;
use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Bus\Event\EventBus;
use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\BoolValueObject;
use App\Core\Domain\ValueObject\DateTimeValueObject;

final class CreateConfigurationHandler implements TransactionalCommandHandler
{
    public function __construct(
        private readonly ConfigurationRepository $configurationRepository,
        private readonly UniqueConfigurationKeySpecificationInterface $uniqueConfigurationKeySpecification,
        private readonly ConfigurationValueCaster $caster,
        private readonly ConfigurationValueEncryptor $encryptor,
        private readonly EventBus $eventBus,
        private readonly DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(CreateConfigurationCommand $command): void
    {
        $key = new ConfigurationKey($command->key);
        $type = new ConfigurationType($command->type);

        $this->uniqueConfigurationKeySpecification->isUnique($key);
        $this->caster->assertValid($command->value, $type);

        if ($command->isSensitive) {
            $encrypted = $this->encryptor->encrypt($command->value);
            $storedValue = $encrypted->ciphertext;
            $keyVersion = $encrypted->keyVersion;
        } else {
            $storedValue = $command->value;
            $keyVersion = null;
        }

        $now = new DateTimeValueObject($this->dateTimeProvider->now());

        $configuration = new Configuration(
            id: new ConfigurationId($command->id),
            key: $key,
            value: new ConfigurationValue($storedValue),
            type: $type,
            description: $command->description !== null ? new ConfigurationDescription($command->description) : null,
            isSensitive: new BoolValueObject($command->isSensitive),
            keyVersion: $keyVersion,
            createdAt: $now,
            updatedAt: $now,
            changedBy: $command->changedBy,
        );

        $this->configurationRepository->save($configuration);

        $this->eventBus->publish(...$configuration->pullDomainEvents());
    }
}
