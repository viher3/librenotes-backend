<?php

declare(strict_types=1);

namespace App\Notes\Domain\ValueObject;

use App\Core\Domain\ValueObject\ArrayValueObject;
use App\Notes\Domain\Exception\InvalidTag;

/**
 * Free-form labels attached to notes and links. Names are trimmed and lower-cased so `Work` and
 * `work ` are the same tag, duplicates collapse, and the order of first appearance is kept.
 */
class Tags extends ArrayValueObject
{
    public const int MAX_TAGS = 20;
    public const int MAX_LENGTH = 50;

    /**
     * @param array<int|string, mixed> $value
     */
    public function __construct(array $value)
    {
        $names = [];

        foreach ($value as $name) {
            if (!is_string($name)) {
                throw new InvalidTag('tags must be strings');
            }

            $normalized = self::normalize($name);

            if ($normalized === '' || mb_strlen($normalized) > self::MAX_LENGTH) {
                throw new InvalidTag(sprintf('a tag must have between 1 and %d characters', self::MAX_LENGTH));
            }

            $names[$normalized] = $normalized;
        }

        if (count($names) > self::MAX_TAGS) {
            throw new InvalidTag(sprintf('at most %d tags are allowed', self::MAX_TAGS));
        }

        parent::__construct(array_values($names));
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public static function normalize(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    /**
     * @return string[]
     */
    public function names(): array
    {
        /** @var string[] $names */
        $names = $this->value;

        return $names;
    }
}
