<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractJsonValueObjectType;
use App\Notes\Domain\ValueObject\Tags;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * @extends AbstractJsonValueObjectType<Tags>
 */
final class TagsType extends AbstractJsonValueObjectType
{
    public const string NAME = 'notes_tags';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return Tags::class;
    }

    /**
     * Rows created before tags existed have no value: they simply have no tags.
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): Tags
    {
        return parent::convertToPHPValue($value, $platform) ?? Tags::empty();
    }
}
