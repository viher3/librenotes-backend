<?php

declare(strict_types=1);

namespace App\Notification\Application\DeleteTemplate;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Notification\Domain\Exception\NotificationTemplateNotFoundException;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateId;
use App\Notification\Domain\Repository\NotificationTemplateRepository;

final class DeleteNotificationTemplateHandler implements TransactionalCommandHandler
{
    public function __construct(
        private readonly NotificationTemplateRepository $notificationTemplateRepository
    ) {
    }

    public function __invoke(DeleteNotificationTemplateCommand $command): void
    {
        $template = $this->notificationTemplateRepository->findById(new NotificationTemplateId($command->id));

        if (!$template) {
            throw new NotificationTemplateNotFoundException($command->id);
        }

        $this->notificationTemplateRepository->delete($template);
    }
}
