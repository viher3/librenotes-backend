<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractJsonValueObjectType;
use App\Notification\Domain\Model\ValueObject\NotificationPayload;

/**
 * @extends AbstractJsonValueObjectType<NotificationPayload>
 */
final class NotificationPayloadType extends AbstractJsonValueObjectType
{
    public const string NAME = 'notification_payload';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return NotificationPayload::class;
    }
}
