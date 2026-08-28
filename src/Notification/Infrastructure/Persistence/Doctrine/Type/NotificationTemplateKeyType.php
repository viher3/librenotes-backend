<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateKey;

/**
 * @extends AbstractStringValueObjectType<NotificationTemplateKey>
 */
final class NotificationTemplateKeyType extends AbstractStringValueObjectType
{
    public const string NAME = 'notification_template_key';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return NotificationTemplateKey::class;
    }
}
