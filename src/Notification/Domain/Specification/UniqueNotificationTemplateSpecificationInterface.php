<?php

declare(strict_types=1);

namespace App\Notification\Domain\Specification;

use App\Notification\Domain\Model\ValueObject\NotificationChannel;
use App\Notification\Domain\Model\ValueObject\NotificationLocale;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateKey;

interface UniqueNotificationTemplateSpecificationInterface
{
    public function isUnique(
        NotificationTemplateKey $key,
        NotificationChannel $channel,
        NotificationLocale $locale
    ): bool;
}
