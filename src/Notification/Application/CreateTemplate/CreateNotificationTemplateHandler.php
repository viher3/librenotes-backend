<?php

declare(strict_types=1);

namespace App\Notification\Application\CreateTemplate;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\BoolValueObject;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Notification\Domain\Model\NotificationTemplate;
use App\Notification\Domain\Model\ValueObject\NotificationBody;
use App\Notification\Domain\Model\ValueObject\NotificationChannel;
use App\Notification\Domain\Model\ValueObject\NotificationLocale;
use App\Notification\Domain\Model\ValueObject\NotificationSubject;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateId;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateKey;
use App\Notification\Domain\Repository\NotificationTemplateRepository;
use App\Notification\Domain\Specification\UniqueNotificationTemplateSpecificationInterface;

final class CreateNotificationTemplateHandler implements TransactionalCommandHandler
{
    public function __construct(
        private readonly NotificationTemplateRepository $notificationTemplateRepository,
        private readonly UniqueNotificationTemplateSpecificationInterface $uniqueNotificationTemplateSpecification,
        private readonly DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(CreateNotificationTemplateCommand $command): void
    {
        $key = new NotificationTemplateKey($command->key);
        $channel = new NotificationChannel($command->channel);
        $locale = new NotificationLocale($command->locale);

        $this->uniqueNotificationTemplateSpecification->isUnique($key, $channel, $locale);

        $now = new DateTimeValueObject($this->dateTimeProvider->now());

        $template = new NotificationTemplate(
            id: new NotificationTemplateId($command->id),
            key: $key,
            channel: $channel,
            locale: $locale,
            subject: new NotificationSubject($command->subject),
            body: new NotificationBody($command->body),
            isActive: new BoolValueObject($command->isActive),
            createdAt: $now,
            updatedAt: $now,
        );

        $this->notificationTemplateRepository->save($template);
    }
}
