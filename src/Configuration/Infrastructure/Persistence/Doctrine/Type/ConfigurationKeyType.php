<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\Persistence\Doctrine\Type;

use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;
use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;

/**
 * @extends AbstractStringValueObjectType<ConfigurationKey>
 */
final class ConfigurationKeyType extends AbstractStringValueObjectType
{
    public const string NAME = 'configuration_key';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return ConfigurationKey::class;
    }
}
