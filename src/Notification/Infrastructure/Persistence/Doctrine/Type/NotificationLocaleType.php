<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Notification\Domain\Model\ValueObject\NotificationLocale;

/**
 * @extends AbstractStringValueObjectType<NotificationLocale>
 */
final class NotificationLocaleType extends AbstractStringValueObjectType
{
    public const string NAME = 'notification_locale';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return NotificationLocale::class;
    }
}
