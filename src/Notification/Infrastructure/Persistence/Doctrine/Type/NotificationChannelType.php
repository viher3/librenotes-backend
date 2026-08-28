<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Notification\Domain\Model\ValueObject\NotificationChannel;

/**
 * @extends AbstractStringValueObjectType<NotificationChannel>
 */
final class NotificationChannelType extends AbstractStringValueObjectType
{
    public const string NAME = 'notification_channel';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return NotificationChannel::class;
    }
}
