<?php

declare(strict_types=1);

namespace App\Notes\Domain\ValueObject;

use App\Core\Domain\ValueObject\StringValueObject;
use App\Notes\Domain\Exception\InvalidLinkUrl;

/**
 * Absolute http(s) URL. Other schemes (javascript:, data:, file:...) are rejected because links
 * are rendered as clickable anchors by clients.
 */
class LinkUrl extends StringValueObject
{
    public const int MAX_LENGTH = 2048;

    public function __construct(string $value)
    {
        $value = trim($value);
        $parts = parse_url($value);

        if (
            $value === ''
            || strlen($value) > self::MAX_LENGTH
            || preg_match('/\s/', $value) === 1
            || $parts === false
            || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || ($parts['host'] ?? '') === ''
        ) {
            throw new InvalidLinkUrl($value);
        }

        parent::__construct($value);
    }
}
