<?php

declare(strict_types=1);

namespace App\Configuration\Application\Update;

use App\Configuration\Domain\Exception\ConfigurationNotFoundException;
use App\Configuration\Domain\Model\ValueObject\ConfigurationDescription;
use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;
use App\Configuration\Domain\Model\ValueObject\ConfigurationType;
use App\Configuration\Domain\Model\ValueObject\ConfigurationValue;
use App\Configuration\Domain\Repository\ConfigurationRepository;
use App\Configuration\Domain\Service\ConfigurationValueCaster;
use App\Configuration\Domain\Service\ConfigurationValueEncryptor;
use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Bus\Event\EventBus;
use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\BoolValueObject;
use App\Core\Domain\ValueObject\DateTimeValueObject;

final class UpdateConfigurationHandler implements TransactionalCommandHandler
{
    public function __construct(
        private readonly ConfigurationRepository $configurationRepository,
        private readonly ConfigurationValueCaster $caster,
        private readonly ConfigurationValueEncryptor $encryptor,
        private readonly EventBus $eventBus,
        private readonly DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(UpdateConfigurationCommand $command): void
    {
        $configuration = $this->configurationRepository->findByKey(new ConfigurationKey($command->key));

        if (!$configuration) {
            throw new ConfigurationNotFoundException($command->key);
        }

        $type = $command->type !== null
            ? new ConfigurationType($command->type)
            : $configuration->getType();

        if ($command->value !== null) {
            $plaintext = $command->value;
        } elseif ($configuration->isSensitive()->value()) {
            $plaintext = $this->encryptor->decrypt($configuration->getValue()->value(), $configuration->getKeyVersion());
        } else {
            $plaintext = $configuration->getValue()->value();
        }

        $this->caster->assertValid($plaintext, $type);

        $isSensitive = $command->isSensitive ?? $configuration->isSensitive()->value();

        if ($isSensitive) {
            $encrypted = $this->encryptor->encrypt($plaintext);
            $storedValue = $encrypted->ciphertext;
            $keyVersion = $encrypted->keyVersion;
        } else {
            $storedValue = $plaintext;
            $keyVersion = null;
        }

        $description = $command->description !== null
            ? new ConfigurationDescription($command->description)
            : $configuration->getDescription();

        $configuration->changeValue(
            value: new ConfigurationValue($storedValue),
            type: $type,
            description: $description,
            isSensitive: new BoolValueObject($isSensitive),
            keyVersion: $keyVersion,
            updatedAt: new DateTimeValueObject($this->dateTimeProvider->now()),
            changedBy: $command->changedBy,
        );

        $this->configurationRepository->save($configuration);

        $this->eventBus->publish(...$configuration->pullDomainEvents());
    }
}
