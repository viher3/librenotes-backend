<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Repository;

use App\Configuration\Domain\Model\ConfigurationHistory;
use App\Configuration\Domain\Model\ValueObject\ConfigurationHistoryId;
use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;

interface ConfigurationHistoryRepository
{
    public function save(ConfigurationHistory $entry): void;

    /**
     * @return ConfigurationHistory[]
     */
    public function findByConfigurationKey(ConfigurationKey $key, int $page, int $perPage): array;

    public function countByConfigurationKey(ConfigurationKey $key): int;

    /**
     * Rows where either side (previous_value/new_value) is sensitive and not yet
     * encrypted with $targetVersion. Used only by the key rotation command.
     *
     * @return ConfigurationHistory[]
     */
    public function findSensitiveNotOnKeyVersion(int $targetVersion, int $limit, int $offset): array;

    /**
     * Direct storage update, bypassing the aggregate/event pipeline. See
     * ConfigurationRepository::rotateEncryptedValue() for why.
     */
    public function rotateEncryptedValues(
        ConfigurationHistoryId $id,
        ?string $previousCiphertext,
        ?int $previousKeyVersion,
        ?string $newCiphertext,
        ?int $newKeyVersion
    ): void;
}
