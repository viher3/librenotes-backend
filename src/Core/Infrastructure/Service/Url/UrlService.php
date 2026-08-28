<?php

namespace App\Core\Infrastructure\Service\Url;

final class UrlService
{
    /**
     * @param string $url
     * @param string ...$values
     * @return string
     */
    public static function concat(
        string $url,
        string ...$values
    ): string {
        $url = self::removeSlashAtLastPosition($url);

        foreach ($values as $value) {
            $value = self::removeSlashAtFirstPosition($value);
            $value = self::removeSlashAtLastPosition($value);
            $url .= '/' . $value;
        }

        return $url;
    }

    /**
     * @param string $value
     * @return string
     */
    public static function removeSlashAtLastPosition(string $value): string
    {
        if (!self::hasSlashAtLastPosition($value)) {
            return $value;
        }

        return substr($value, 0, strlen($value) - 1);
    }

    /**
     * @param string $value
     * @return string
     */
    public static function removeSlashAtFirstPosition(string $value): string
    {
        if (!self::hasSlashAtFirstPosition($value)) {
            return $value;
        }

        return substr($value, 1, strlen($value));
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function hasSlashAtLastPosition(string $value): bool
    {
        return str_ends_with($value, '/');
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function hasSlashAtFirstPosition(string $value): bool
    {
        return str_starts_with($value, '/');
    }
}