<?php

declare(strict_types=1);

namespace App\Notification\Application\TemplateDetail;

use App\Core\Application\Query\QueryHandler;
use App\Notification\Domain\Exception\NotificationTemplateNotFoundException;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateId;
use App\Notification\Domain\Repository\NotificationTemplateRepository;

final class NotificationTemplateDetailHandler implements QueryHandler
{
    public function __construct(
        private readonly NotificationTemplateRepository $notificationTemplateRepository
    ) {
    }

    public function __invoke(NotificationTemplateDetailQuery $query): NotificationTemplateDetailResponse
    {
        $template = $this->notificationTemplateRepository->findById(new NotificationTemplateId($query->id));

        if (!$template) {
            throw new NotificationTemplateNotFoundException($query->id);
        }

        return new NotificationTemplateDetailResponse($template);
    }
}
