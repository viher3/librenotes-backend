<?php

declare(strict_types=1);

namespace App\Notification\Application\TemplateSearch;

use App\Core\Application\Query\QueryHandler;
use App\Notification\Domain\Repository\NotificationTemplateRepository;

final class NotificationTemplateSearchHandler implements QueryHandler
{
    public function __construct(
        private readonly NotificationTemplateRepository $notificationTemplateRepository
    ) {
    }

    public function __invoke(NotificationTemplateSearchQuery $query): NotificationTemplateSearchResponse
    {
        $templates = $this->notificationTemplateRepository->search(
            ...$query->getAll()
        );

        $total = $this->notificationTemplateRepository->searchCount(
            $query->getFilters()
        );

        return new NotificationTemplateSearchResponse(
            $templates,
            $total,
            $query->getPage(),
            $query->getItemsPerPage()
        );
    }
}
