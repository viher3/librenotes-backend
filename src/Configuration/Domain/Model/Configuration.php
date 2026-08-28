<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Model;

use App\Configuration\Domain\Event\ConfigurationCreated;
use App\Configuration\Domain\Event\ConfigurationDeleted;
use App\Configuration\Domain\Event\ConfigurationValueChanged;
use App\Configuration\Domain\Model\ValueObject\ConfigurationDescription;
use App\Configuration\Domain\Model\ValueObject\ConfigurationId;
use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;
use App\Configuration\Domain\Model\ValueObject\ConfigurationType;
use App\Configuration\Domain\Model\ValueObject\ConfigurationValue;
use App\Core\Domain\Aggregate\AggregateRoot;
use App\Core\Domain\ValueObject\BoolValueObject;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use Ramsey\Uuid\Uuid as RamseyUuid;

class Configuration extends AggregateRoot
{
    public function __construct(
        private readonly ConfigurationId $id,
        private readonly ConfigurationKey $key,
        private ConfigurationValue $value,
        private ConfigurationType $type,
        private ?ConfigurationDescription $description,
        private BoolValueObject $isSensitive,
        private ?int $keyVersion,
        private readonly DateTimeValueObject $createdAt,
        private DateTimeValueObject $updatedAt,
        string $changedBy
    ) {
        $this->record(new ConfigurationCreated(
            id: $this->id->value(),
            key: $this->key->value(),
            value: $this->value->value(),
            type: $this->type->value(),
            description: $this->description?->value(),
            isSensitive: $this->isSensitive->value(),
            keyVersion: $this->keyVersion,
            changedBy: $changedBy,
            eventId: RamseyUuid::uuid4()->toString(),
            occurredOn: $this->createdAt->value()->format(DateTimeValueObject::DATETIME_DEFAULT_FORMAT),
        ));
    }

    public function changeValue(
        ConfigurationValue $value,
        ConfigurationType $type,
        ?ConfigurationDescription $description,
        BoolValueObject $isSensitive,
        ?int $keyVersion,
        DateTimeValueObject $updatedAt,
        string $changedBy
    ): void {
        $previousValue = $this->value;
        $previousKeyVersion = $this->keyVersion;

        $this->value = $value;
        $this->type = $type;
        $this->description = $description;
        $this->isSensitive = $isSensitive;
        $this->keyVersion = $keyVersion;
        $this->updatedAt = $updatedAt;

        $this->record(new ConfigurationValueChanged(
            id: $this->id->value(),
            key: $this->key->value(),
            previousValue: $previousValue->value(),
            previousKeyVersion: $previousKeyVersion,
            newValue: $this->value->value(),
            type: $this->type->value(),
            description: $this->description?->value(),
            isSensitive: $this->isSensitive->value(),
            newKeyVersion: $this->keyVersion,
            changedBy: $changedBy,
            eventId: RamseyUuid::uuid4()->toString(),
            occurredOn: $updatedAt->value()->format(DateTimeValueObject::DATETIME_DEFAULT_FORMAT),
        ));
    }

    public function delete(DateTimeValueObject $deletedAt, string $changedBy): void
    {
        $this->record(new ConfigurationDeleted(
            id: $this->id->value(),
            key: $this->key->value(),
            lastValue: $this->value->value(),
            lastKeyVersion: $this->keyVersion,
            changedBy: $changedBy,
            eventId: RamseyUuid::uuid4()->toString(),
            occurredOn: $deletedAt->value()->format(DateTimeValueObject::DATETIME_DEFAULT_FORMAT),
        ));
    }

    public function getId(): ConfigurationId
    {
        return $this->id;
    }

    public function getKey(): ConfigurationKey
    {
        return $this->key;
    }

    public function getValue(): ConfigurationValue
    {
        return $this->value;
    }

    public function getType(): ConfigurationType
    {
        return $this->type;
    }

    public function getDescription(): ?ConfigurationDescription
    {
        return $this->description;
    }

    public function isSensitive(): BoolValueObject
    {
        return $this->isSensitive;
    }

    public function getKeyVersion(): ?int
    {
        return $this->keyVersion;
    }

    public function getCreatedAt(): DateTimeValueObject
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeValueObject
    {
        return $this->updatedAt;
    }
}
