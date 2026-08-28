<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractIntValueObjectType;
use App\Notification\Domain\Model\ValueObject\NotificationAttempts;

/**
 * @extends AbstractIntValueObjectType<NotificationAttempts>
 */
final class NotificationAttemptsType extends AbstractIntValueObjectType
{
    public const string NAME = 'notification_attempts';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return NotificationAttempts::class;
    }
}
