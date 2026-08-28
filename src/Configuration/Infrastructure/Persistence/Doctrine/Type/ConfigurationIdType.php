<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\Persistence\Doctrine\Type;

use App\Configuration\Domain\Model\ValueObject\ConfigurationId;
use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;

/**
 * @extends AbstractStringValueObjectType<ConfigurationId>
 */
final class ConfigurationIdType extends AbstractStringValueObjectType
{
    public const string NAME = 'configuration_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return ConfigurationId::class;
    }
}
