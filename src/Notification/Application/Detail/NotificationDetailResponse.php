<?php

declare(strict_types=1);

namespace App\Notification\Application\Detail;

use App\Core\Application\Query\QueryResponse;
use App\Notification\Domain\Model\Notification;

final class NotificationDetailResponse implements QueryResponse
{
    public function __construct(
        private readonly Notification $notification
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getResult(): array
    {
        return [
            'id' => $this->notification->getId()->value(),
            'channel' => $this->notification->getChannel()->value(),
            'template_key' => $this->notification->getTemplateKey()->value(),
            'locale' => $this->notification->getLocale()->value(),
            'recipient' => $this->notification->getRecipient()->value(),
            'recipient_id' => $this->notification->getRecipientId()?->value(),
            'payload' => $this->notification->getPayload()->value(),
            'subject' => $this->notification->getSubject()?->value(),
            'status' => $this->notification->getStatus()->value(),
            'attempts' => $this->notification->getAttempts()->value(),
            'last_error' => $this->notification->getLastError(),
            'provider_message_id' => $this->notification->getProviderMessageId(),
            'sent_at' => $this->notification->getSentAt()?->value()->format(DATE_ATOM),
            'created_at' => $this->notification->getCreatedAt()->value()->format(DATE_ATOM),
            'updated_at' => $this->notification->getUpdatedAt()->value()->format(DATE_ATOM),
        ];
    }
}
