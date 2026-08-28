<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Service;

use App\Configuration\Domain\Exception\InvalidConfigurationValueException;
use App\Configuration\Domain\Model\ValueObject\ConfigurationType;

/**
 * Converts between a configuration's raw stored string form and its declared
 * PHP type. Pure logic, no infrastructure dependency, so it stays a plain
 * final class rather than an interface with a single implementation.
 */
final class ConfigurationValueCaster
{
    public function assertValid(string $rawValue, ConfigurationType $type): void
    {
        $isValid = match ($type->value()) {
            ConfigurationType::STRING => true,
            ConfigurationType::INTEGER => (bool) preg_match('/^-?\d+$/', $rawValue),
            ConfigurationType::BOOLEAN => in_array($rawValue, ['true', 'false', '1', '0'], true),
            ConfigurationType::JSON => $this->isValidJson($rawValue),
            default => false,
        };

        if (!$isValid) {
            throw new InvalidConfigurationValueException($rawValue, $type->value());
        }
    }

    public function cast(string $rawValue, ConfigurationType $type): string|int|bool|array
    {
        return match ($type->value()) {
            ConfigurationType::STRING => $rawValue,
            ConfigurationType::INTEGER => (int) $rawValue,
            ConfigurationType::BOOLEAN => in_array($rawValue, ['true', '1'], true),
            ConfigurationType::JSON => json_decode($rawValue, true),
            default => $rawValue,
        };
    }

    private function isValidJson(string $rawValue): bool
    {
        json_decode($rawValue);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
