<?php

declare(strict_types=1);

namespace App\Notification\Domain\Model;

use App\Core\Domain\Aggregate\AggregateRoot;
use App\Core\Domain\ValueObject\BoolValueObject;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Notification\Domain\Model\ValueObject\NotificationBody;
use App\Notification\Domain\Model\ValueObject\NotificationChannel;
use App\Notification\Domain\Model\ValueObject\NotificationLocale;
use App\Notification\Domain\Model\ValueObject\NotificationSubject;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateId;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateKey;

/**
 * Extends AggregateRoot only because DoctrineRepository::persist()/remove() require it.
 * It records no domain events: nothing in the system reacts to a template being
 * created, updated or deleted, and event.bus rejects a publish with no handlers.
 */
class NotificationTemplate extends AggregateRoot
{
    public function __construct(
        private readonly NotificationTemplateId $id,
        private readonly NotificationTemplateKey $key,
        private readonly NotificationChannel $channel,
        private readonly NotificationLocale $locale,
        private NotificationSubject $subject,
        private NotificationBody $body,
        private BoolValueObject $isActive,
        private readonly DateTimeValueObject $createdAt,
        private DateTimeValueObject $updatedAt
    ) {
    }

    public function update(
        NotificationSubject $subject,
        NotificationBody $body,
        BoolValueObject $isActive,
        DateTimeValueObject $updatedAt
    ): void {
        $this->subject = $subject;
        $this->body = $body;
        $this->isActive = $isActive;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): NotificationTemplateId
    {
        return $this->id;
    }

    public function getKey(): NotificationTemplateKey
    {
        return $this->key;
    }

    public function getChannel(): NotificationChannel
    {
        return $this->channel;
    }

    public function getLocale(): NotificationLocale
    {
        return $this->locale;
    }

    public function getSubject(): NotificationSubject
    {
        return $this->subject;
    }

    public function getBody(): NotificationBody
    {
        return $this->body;
    }

    public function isActive(): BoolValueObject
    {
        return $this->isActive;
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
