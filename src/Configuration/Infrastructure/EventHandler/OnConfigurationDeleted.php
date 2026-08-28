<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\EventHandler;

use App\Configuration\Domain\Event\ConfigurationDeleted;
use App\Configuration\Domain\EventHandler\ConfigurationDeletedEventHandler;
use App\Configuration\Domain\Model\ConfigurationHistory;
use App\Configuration\Domain\Model\ValueObject\ConfigurationHistoryAction;
use App\Configuration\Domain\Model\ValueObject\ConfigurationHistoryId;
use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;
use App\Configuration\Domain\Model\ValueObject\ConfigurationValue;
use App\Configuration\Domain\Repository\ConfigurationHistoryRepository;
use App\Configuration\Infrastructure\Cache\ConfigurationValueCache;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Core\Domain\ValueObject\Uuid;

final class OnConfigurationDeleted implements ConfigurationDeletedEventHandler
{
    public function __construct(
        private readonly ConfigurationHistoryRepository $historyRepository,
        private readonly ConfigurationValueCache $cache
    ) {
    }

    public function __invoke(ConfigurationDeleted $event): void
    {
        $this->historyRepository->save(new ConfigurationHistory(
            id: ConfigurationHistoryId::random(),
            // The live row is already gone by the time this runs (events publish
            // after the repository delete) — no id left to point at.
            configurationId: null,
            configurationKey: new ConfigurationKey($event->key()),
            action: new ConfigurationHistoryAction(ConfigurationHistoryAction::DELETED),
            previousValue: new ConfigurationValue($event->lastValue()),
            previousValueKeyVersion: $event->lastKeyVersion(),
            newValue: null,
            newValueKeyVersion: null,
            changedBy: new Uuid($event->changedBy()),
            changedAt: new DateTimeValueObject(new \DateTimeImmutable($event->occurredOn())),
        ));

        $this->cache->invalidate($event->key());
    }
}
