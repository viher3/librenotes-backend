<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateId;

/**
 * @extends AbstractStringValueObjectType<NotificationTemplateId>
 */
final class NotificationTemplateIdType extends AbstractStringValueObjectType
{
    public const string NAME = 'notification_template_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return NotificationTemplateId::class;
    }
}
