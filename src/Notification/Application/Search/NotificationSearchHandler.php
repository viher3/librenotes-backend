<?php

declare(strict_types=1);

namespace App\Notification\Application\Search;

use App\Core\Application\Query\QueryHandler;
use App\Notification\Domain\Repository\NotificationRepository;

final class NotificationSearchHandler implements QueryHandler
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository
    ) {
    }

    public function __invoke(NotificationSearchQuery $query): NotificationSearchResponse
    {
        $notifications = $this->notificationRepository->search(
            ...$query->getAll()
        );

        $total = $this->notificationRepository->searchCount(
            $query->getFilters()
        );

        return new NotificationSearchResponse(
            $notifications,
            $total,
            $query->getPage(),
            $query->getItemsPerPage()
        );
    }
}
