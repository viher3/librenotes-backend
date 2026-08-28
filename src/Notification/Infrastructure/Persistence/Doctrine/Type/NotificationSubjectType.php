<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Notification\Domain\Model\ValueObject\NotificationSubject;

/**
 * @extends AbstractStringValueObjectType<NotificationSubject>
 */
final class NotificationSubjectType extends AbstractStringValueObjectType
{
    public const string NAME = 'notification_subject';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return NotificationSubject::class;
    }
}
