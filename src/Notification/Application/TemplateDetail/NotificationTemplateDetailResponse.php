<?php

declare(strict_types=1);

namespace App\Notification\Application\TemplateDetail;

use App\Core\Application\Query\QueryResponse;
use App\Notification\Domain\Model\NotificationTemplate;

final class NotificationTemplateDetailResponse implements QueryResponse
{
    public function __construct(
        private readonly NotificationTemplate $template
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getResult(): array
    {
        return [
            'id' => $this->template->getId()->value(),
            'key' => $this->template->getKey()->value(),
            'channel' => $this->template->getChannel()->value(),
            'locale' => $this->template->getLocale()->value(),
            'subject' => $this->template->getSubject()->value(),
            'body' => $this->template->getBody()->value(),
            'is_active' => $this->template->isActive()->value(),
            'created_at' => $this->template->getCreatedAt()->value()->format(DATE_ATOM),
            'updated_at' => $this->template->getUpdatedAt()->value()->format(DATE_ATOM),
        ];
    }
}
