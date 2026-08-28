<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Specification;

use App\Core\Domain\Specification\AbstractSpecification;
use App\Notification\Domain\Exception\NotificationTemplateAlreadyExistsException;
use App\Notification\Domain\Model\ValueObject\NotificationChannel;
use App\Notification\Domain\Model\ValueObject\NotificationLocale;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateKey;
use App\Notification\Domain\Repository\NotificationTemplateRepository;
use App\Notification\Domain\Specification\UniqueNotificationTemplateSpecificationInterface;

final class UniqueNotificationTemplateSpecification extends AbstractSpecification implements UniqueNotificationTemplateSpecificationInterface
{
    public function __construct(
        private readonly NotificationTemplateRepository $notificationTemplateRepository
    ) {
    }

    public function isSatisfiedBy(mixed $value): bool
    {
        [$key, $channel, $locale] = $value;

        if ($this->notificationTemplateRepository->existsByKeyChannelAndLocale($key, $channel, $locale)) {
            throw new NotificationTemplateAlreadyExistsException($key->value(), $channel->value(), $locale->value());
        }

        return true;
    }

    public function isUnique(
        NotificationTemplateKey $key,
        NotificationChannel $channel,
        NotificationLocale $locale
    ): bool {
        return $this->isSatisfiedBy([$key, $channel, $locale]);
    }
}
