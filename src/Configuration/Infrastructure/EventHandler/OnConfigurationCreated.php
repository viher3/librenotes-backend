<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\EventHandler;

use App\Configuration\Domain\Event\ConfigurationCreated;
use App\Configuration\Domain\EventHandler\ConfigurationCreatedEventHandler;
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

/**
 * Reacts to ConfigurationCreated by writing the ledger entry and invalidating the
 * read cache. Kept as one handler rather than two: both are secondary state derived
 * from the same event, and Messenger wiring in this codebase is one class per
 * message type (no multi-method #[AsMessageHandler]), so splitting further would
 * only multiply near-empty files.
 */
final class OnConfigurationCreated implements ConfigurationCreatedEventHandler
{
    public function __construct(
        private readonly ConfigurationHistoryRepository $historyRepository,
        private readonly ConfigurationValueCache $cache
    ) {
    }

    public function __invoke(ConfigurationCreated $event): void
    {
        $this->historyRepository->save(new ConfigurationHistory(
            id: ConfigurationHistoryId::random(),
            configurationId: new ConfigurationId($event->aggregateId()),
            configurationKey: new ConfigurationKey($event->key()),
            action: new ConfigurationHistoryAction(ConfigurationHistoryAction::CREATED),
            previousValue: null,
            previousValueKeyVersion: null,
            newValue: new ConfigurationValue($event->value()),
            newValueKeyVersion: $event->keyVersion(),
            changedBy: new Uuid($event->changedBy()),
            changedAt: new DateTimeValueObject(new \DateTimeImmutable($event->occurredOn())),
        ));

        $this->cache->invalidate($event->key());
    }
}
