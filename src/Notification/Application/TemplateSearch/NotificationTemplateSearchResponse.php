<?php

declare(strict_types=1);

namespace App\Notification\Application\TemplateSearch;

use App\Core\Application\Query\SearchResponse;
use App\Notification\Domain\Model\NotificationTemplate;

final class NotificationTemplateSearchResponse implements SearchResponse
{
    /**
     * @param NotificationTemplate[] $templates
     */
    public function __construct(
        private readonly array $templates,
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

        foreach ($this->templates as $template) {
            $data[] = [
                'id' => $template->getId()->value(),
                'key' => $template->getKey()->value(),
                'channel' => $template->getChannel()->value(),
                'locale' => $template->getLocale()->value(),
                'subject' => $template->getSubject()->value(),
                'is_active' => $template->isActive()->value(),
                'created_at' => $template->getCreatedAt()->value()->format(DATE_ATOM),
                'updated_at' => $template->getUpdatedAt()->value()->format(DATE_ATOM),
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
