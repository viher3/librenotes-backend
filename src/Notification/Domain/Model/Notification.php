<?php

declare(strict_types=1);

namespace App\Notification\Domain\Model;

use App\Core\Domain\Aggregate\AggregateRoot;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Core\Domain\ValueObject\Uuid;
use App\Notification\Domain\Event\NotificationFailed;
use App\Notification\Domain\Event\NotificationQueued;
use App\Notification\Domain\Event\NotificationSent;
use App\Notification\Domain\Exception\NotificationNotRetryableException;
use App\Notification\Domain\Model\ValueObject\NotificationAttempts;
use App\Notification\Domain\Model\ValueObject\NotificationBody;
use App\Notification\Domain\Model\ValueObject\NotificationChannel;
use App\Notification\Domain\Model\ValueObject\NotificationId;
use App\Notification\Domain\Model\ValueObject\NotificationLocale;
use App\Notification\Domain\Model\ValueObject\NotificationPayload;
use App\Notification\Domain\Model\ValueObject\NotificationRecipient;
use App\Notification\Domain\Model\ValueObject\NotificationStatus;
use App\Notification\Domain\Model\ValueObject\NotificationSubject;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateKey;
use Ramsey\Uuid\Uuid as RamseyUuid;

class Notification extends AggregateRoot
{
    public function __construct(
        private readonly NotificationId $id,
        private readonly NotificationChannel $channel,
        private readonly NotificationTemplateKey $templateKey,
        private readonly NotificationLocale $locale,
        private readonly NotificationRecipient $recipient,
        private readonly ?Uuid $recipientId,
        private readonly NotificationPayload $payload,
        private ?NotificationSubject $subject,
        private ?NotificationBody $body,
        private NotificationStatus $status,
        private NotificationAttempts $attempts,
        private ?string $lastError,
        private ?string $providerMessageId,
        private ?DateTimeValueObject $sentAt,
        private readonly DateTimeValueObject $createdAt,
        private DateTimeValueObject $updatedAt
    ) {
        $this->record(new NotificationQueued(
            id: $this->id->value(),
            channel: $this->channel->value(),
            templateKey: $this->templateKey->value(),
            locale: $this->locale->value(),
            recipient: $this->recipient->value(),
            recipientId: $this->recipientId?->value(),
            payload: $this->payload->value(),
            eventId: RamseyUuid::uuid4()->toString(),
            occurredOn: $this->createdAt->value()->format(DateTimeValueObject::DATETIME_DEFAULT_FORMAT),
        ));
    }

    public function markAsSent(
        NotificationSubject $subject,
        NotificationBody $body,
        ?string $providerMessageId,
        DateTimeValueObject $at
    ): void {
        $this->subject = $subject;
        $this->body = $body;
        $this->status = new NotificationStatus(NotificationStatus::SENT);
        $this->providerMessageId = $providerMessageId;
        $this->sentAt = $at;
        $this->updatedAt = $at;

        $this->record(new NotificationSent(
            id: $this->id->value(),
            channel: $this->channel->value(),
            recipient: $this->recipient->value(),
            providerMessageId: $this->providerMessageId,
            eventId: RamseyUuid::uuid4()->toString(),
            occurredOn: $at->value()->format(DateTimeValueObject::DATETIME_DEFAULT_FORMAT),
        ));
    }

    public function markAsFailed(
        ?NotificationSubject $subject,
        ?NotificationBody $body,
        string $error,
        DateTimeValueObject $at
    ): void {
        if ($subject !== null) {
            $this->subject = $subject;
        }

        if ($body !== null) {
            $this->body = $body;
        }

        $this->status = new NotificationStatus(NotificationStatus::FAILED);
        $this->attempts = $this->attempts->increment();
        $this->lastError = $error;
        $this->updatedAt = $at;

        $this->record(new NotificationFailed(
            id: $this->id->value(),
            channel: $this->channel->value(),
            recipient: $this->recipient->value(),
            attempts: $this->attempts->value(),
            error: $this->lastError,
            eventId: RamseyUuid::uuid4()->toString(),
            occurredOn: $at->value()->format(DateTimeValueObject::DATETIME_DEFAULT_FORMAT),
        ));
    }

    public function retry(DateTimeValueObject $at): void
    {
        if (!$this->status->equals(new NotificationStatus(NotificationStatus::FAILED))) {
            throw new NotificationNotRetryableException($this->id->value(), $this->status->value());
        }

        $this->status = new NotificationStatus(NotificationStatus::PENDING);
        $this->lastError = null;
        $this->updatedAt = $at;

        $this->record(new NotificationQueued(
            id: $this->id->value(),
            channel: $this->channel->value(),
            templateKey: $this->templateKey->value(),
            locale: $this->locale->value(),
            recipient: $this->recipient->value(),
            recipientId: $this->recipientId?->value(),
            payload: $this->payload->value(),
            eventId: RamseyUuid::uuid4()->toString(),
            occurredOn: $at->value()->format(DateTimeValueObject::DATETIME_DEFAULT_FORMAT),
        ));
    }

    public function getId(): NotificationId
    {
        return $this->id;
    }

    public function getChannel(): NotificationChannel
    {
        return $this->channel;
    }

    public function getTemplateKey(): NotificationTemplateKey
    {
        return $this->templateKey;
    }

    public function getLocale(): NotificationLocale
    {
        return $this->locale;
    }

    public function getRecipient(): NotificationRecipient
    {
        return $this->recipient;
    }

    public function getRecipientId(): ?Uuid
    {
        return $this->recipientId;
    }

    public function getPayload(): NotificationPayload
    {
        return $this->payload;
    }

    public function getSubject(): ?NotificationSubject
    {
        return $this->subject;
    }

    public function getBody(): ?NotificationBody
    {
        return $this->body;
    }

    public function getStatus(): NotificationStatus
    {
        return $this->status;
    }

    public function getAttempts(): NotificationAttempts
    {
        return $this->attempts;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getProviderMessageId(): ?string
    {
        return $this->providerMessageId;
    }

    public function getSentAt(): ?DateTimeValueObject
    {
        return $this->sentAt;
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
