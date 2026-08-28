<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Notification\Domain\Model\ValueObject\NotificationRecipient;

/**
 * @extends AbstractStringValueObjectType<NotificationRecipient>
 */
final class NotificationRecipientType extends AbstractStringValueObjectType
{
    public const string NAME = 'notification_recipient';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return NotificationRecipient::class;
    }
}
