<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\EventHandler;

use App\Configuration\Domain\Event\ConfigurationValueChanged;
use App\Configuration\Domain\EventHandler\ConfigurationValueChangedEventHandler;
use App\Configuration\Domain\Model\ConfigurationHistory;
use App\Configuration\Domain\Model\ValueObject\ConfigurationHistoryAction;
use App\Configuration\Domain\Model\ValueObject\ConfigurationHistoryId;
use App\Configuration\Domain\Model\ValueObject\ConfigurationId;
use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;
use App\Configuration\Domain\Model\ValueObject\ConfigurationValue;
use App\Configuration\Domain\Repository\ConfigurationHistoryRepository;
use App\Configuration\Infrastructure\Cache\ConfigurationValueCache;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Core\Domain\ValueObject\Uuid;

final class OnConfigurationValueChanged implements ConfigurationValueChangedEventHandler
{
    public function __construct(
        private readonly ConfigurationHistoryRepository $historyRepository,
        private readonly ConfigurationValueCache $cache
    ) {
    }

    public function __invoke(ConfigurationValueChanged $event): void
    {
        $this->historyRepository->save(new ConfigurationHistory(
            id: ConfigurationHistoryId::random(),
            configurationId: new ConfigurationId($event->aggregateId()),
            configurationKey: new ConfigurationKey($event->key()),
            action: new ConfigurationHistoryAction(ConfigurationHistoryAction::UPDATED),
            previousValue: new ConfigurationValue($event->previousValue()),
            previousValueKeyVersion: $event->previousKeyVersion(),
            newValue: new ConfigurationValue($event->newValue()),
            newValueKeyVersion: $event->newKeyVersion(),
            changedBy: new Uuid($event->changedBy()),
            changedAt: new DateTimeValueObject(new \DateTimeImmutable($event->occurredOn())),
        ));

        $this->cache->invalidate($event->key());
    }
}
