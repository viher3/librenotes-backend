<?php

declare(strict_types=1);

namespace App\Notification\Application\Search;

use App\Core\Application\Query\SearchResponse;
use App\Notification\Domain\Model\Notification;

final class NotificationSearchResponse implements SearchResponse
{
    /**
     * @param Notification[] $notifications
     */
    public function __construct(
        private readonly array $notifications,
        private readonly int $total,
        private readonly int $page,
        private readonly int $perPage
    ) {
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @return array<string, mixed>
     */
    public function getResult(): array
    {
        $data = [];

        foreach ($this->notifications as $notification) {
            $data[] = [
                'id' => $notification->getId()->value(),
                'channel' => $notification->getChannel()->value(),
                'template_key' => $notification->getTemplateKey()->value(),
                'locale' => $notification->getLocale()->value(),
                'recipient' => $notification->getRecipient()->value(),
                'recipient_id' => $notification->getRecipientId()?->value(),
                'status' => $notification->getStatus()->value(),
                'attempts' => $notification->getAttempts()->value(),
                'last_error' => $notification->getLastError(),
                'provider_message_id' => $notification->getProviderMessageId(),
                'sent_at' => $notification->getSentAt()?->value()->format(DATE_ATOM),
                'created_at' => $notification->getCreatedAt()->value()->format(DATE_ATOM),
                'updated_at' => $notification->getUpdatedAt()->value()->format(DATE_ATOM),
            ];
        }

        return [
            'meta' => [
                'page' => $this->page,
                'size' => $this->perPage,
                'total' => $this->total,
                'last_page' => $this->perPage > 0 ? (int) ceil($this->total / $this->perPage) : 0,
            ],
            'data' => $data,
        ];
    }
}
