<?php

declare(strict_types=1);

namespace App\Configuration\Application\Delete;

use App\Configuration\Domain\Exception\ConfigurationNotFoundException;
use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;
use App\Configuration\Domain\Repository\ConfigurationRepository;
use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Bus\Event\EventBus;
use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\DateTimeValueObject;

final class DeleteConfigurationHandler implements TransactionalCommandHandler
{
    public function __construct(
        private readonly ConfigurationRepository $configurationRepository,
        private readonly EventBus $eventBus,
        private readonly DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(DeleteConfigurationCommand $command): void
    {
        $configuration = $this->configurationRepository->findByKey(new ConfigurationKey($command->key));

        if (!$configuration) {
            throw new ConfigurationNotFoundException($command->key);
        }

        $configuration->delete(new DateTimeValueObject($this->dateTimeProvider->now()), $command->changedBy);

        $this->configurationRepository->delete($configuration);

        $this->eventBus->publish(...$configuration->pullDomainEvents());
    }
}
