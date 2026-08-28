<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Domain\ValueObject\Uuid;
use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;

/**
 * Wraps the generic Core Uuid, not a Configuration-owned value object: "changed_by"
 * is a loose reference to whichever user acted, not a Configuration domain concept.
 *
 * @extends AbstractStringValueObjectType<Uuid>
 */
final class ConfigurationChangedByType extends AbstractStringValueObjectType
{
    public const string NAME = 'configuration_changed_by';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return Uuid::class;
    }
}
