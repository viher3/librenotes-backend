<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\Persistence\Doctrine\Type;

use App\Configuration\Domain\Model\ValueObject\ConfigurationDescription;
use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractTextValueObjectType;

/**
 * @extends AbstractTextValueObjectType<ConfigurationDescription>
 */
final class ConfigurationDescriptionType extends AbstractTextValueObjectType
{
    public const string NAME = 'configuration_description';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return ConfigurationDescription::class;
    }
}
