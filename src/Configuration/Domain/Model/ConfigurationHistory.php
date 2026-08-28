<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Model;

use App\Configuration\Domain\Model\ValueObject\ConfigurationHistoryAction;
use App\Configuration\Domain\Model\ValueObject\ConfigurationHistoryId;
use App\Configuration\Domain\Model\ValueObject\ConfigurationId;
use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;
use App\Configuration\Domain\Model\ValueObject\ConfigurationValue;
use App\Core\Domain\Aggregate\AggregateRoot;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Core\Domain\ValueObject\Uuid;

/**
 * Append-only ledger entry. Written only by the RecordHistory event handlers,
 * never directly by the Create/Update/Delete use cases.
 */
class ConfigurationHistory extends AggregateRoot
{
    public function __construct(
        private readonly ConfigurationHistoryId $id,
        private readonly ?ConfigurationId $configurationId,
        private readonly ConfigurationKey $configurationKey,
        private readonly ConfigurationHistoryAction $action,
        private readonly ?ConfigurationValue $previousValue,
        private readonly ?int $previousValueKeyVersion,
        private readonly ?ConfigurationValue $newValue,
        private readonly ?int $newValueKeyVersion,
        private readonly Uuid $changedBy,
        private readonly DateTimeValueObject $changedAt
    ) {
    }

    public function getId(): ConfigurationHistoryId
    {
        return $this->id;
    }

    public function getConfigurationId(): ?ConfigurationId
    {
        return $this->configurationId;
    }

    public function getConfigurationKey(): ConfigurationKey
    {
        return $this->configurationKey;
    }

    public function getAction(): ConfigurationHistoryAction
    {
        return $this->action;
    }

    public function getPreviousValue(): ?ConfigurationValue
    {
        return $this->previousValue;
    }

    public function getPreviousValueKeyVersion(): ?int
    {
        return $this->previousValueKeyVersion;
    }

    public function getNewValue(): ?ConfigurationValue
    {
        return $this->newValue;
    }

    public function getNewValueKeyVersion(): ?int
    {
        return $this->newValueKeyVersion;
    }

    public function getChangedBy(): Uuid
    {
        return $this->changedBy;
    }

    public function getChangedAt(): DateTimeValueObject
    {
        return $this->changedAt;
    }
}
