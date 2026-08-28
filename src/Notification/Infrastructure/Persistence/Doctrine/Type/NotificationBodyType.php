<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractTextValueObjectType;
use App\Notification\Domain\Model\ValueObject\NotificationBody;

/**
 * @extends AbstractTextValueObjectType<NotificationBody>
 */
final class NotificationBodyType extends AbstractTextValueObjectType
{
    public const string NAME = 'notification_body';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return NotificationBody::class;
    }
}
