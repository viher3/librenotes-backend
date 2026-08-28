<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Domain\ValueObject\Uuid;
use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;

/**
 * Wraps the Core Uuid rather than a module-specific value object: recipientId is a
 * loose cross-module reference kept only for auditing/filtering (see the Fetcher
 * pattern note in the module design — Notification never resolves it back to a User).
 *
 * @extends AbstractStringValueObjectType<Uuid>
 */
final class NotificationRecipientIdType extends AbstractStringValueObjectType
{
    public const string NAME = 'notification_recipient_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return Uuid::class;
    }
}
