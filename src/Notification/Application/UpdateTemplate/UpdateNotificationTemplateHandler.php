<?php

declare(strict_types=1);

namespace App\Notification\Application\UpdateTemplate;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\BoolValueObject;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Notification\Domain\Exception\NotificationTemplateNotFoundException;
use App\Notification\Domain\Model\ValueObject\NotificationBody;
use App\Notification\Domain\Model\ValueObject\NotificationSubject;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateId;
use App\Notification\Domain\Repository\NotificationTemplateRepository;

final class UpdateNotificationTemplateHandler implements TransactionalCommandHandler
{
    public function __construct(
        private readonly NotificationTemplateRepository $notificationTemplateRepository,
        private readonly DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(UpdateNotificationTemplateCommand $command): void
    {
        $template = $this->notificationTemplateRepository->findById(new NotificationTemplateId($command->id));

        if (!$template) {
            throw new NotificationTemplateNotFoundException($command->id);
        }

        $subject = $command->subject !== null
            ? new NotificationSubject($command->subject)
            : $template->getSubject();

        $body = $command->body !== null
            ? new NotificationBody($command->body)
            : $template->getBody();

        $isActive = $command->isActive ?? $template->isActive()->value();

        $template->update(
            subject: $subject,
            body: $body,
            isActive: new BoolValueObject($isActive),
            updatedAt: new DateTimeValueObject($this->dateTimeProvider->now()),
        );

        $this->notificationTemplateRepository->save($template);
    }
}
