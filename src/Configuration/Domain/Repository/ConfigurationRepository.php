<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Repository;

use App\Configuration\Domain\Model\Configuration;
use App\Configuration\Domain\Model\ValueObject\ConfigurationId;
use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;

interface ConfigurationRepository
{
    public function save(Configuration $configuration): void;

    public function findByKey(ConfigurationKey $key): ?Configuration;

    public function findById(ConfigurationId $id): ?Configuration;

    public function delete(Configuration $configuration): void;

    public function existsByKey(ConfigurationKey $key): bool;

    /**
     * @return Configuration[]
     */
    public function search(
        array $filters,
        int $page,
        int $perPage,
        ?string $orderBy = null,
        ?string $orderDirection = null
    ): array;

    public function searchCount(array $filters): int;

    /**
     * Sensitive rows whose stored value is not yet encrypted with $targetVersion.
     * Used only by the key rotation command — never by the CQRS bus use cases.
     *
     * @return Configuration[]
     */
    public function findSensitiveNotOnKeyVersion(int $targetVersion, int $limit, int $offset): array;

    /**
     * Direct storage update, bypassing the aggregate/event pipeline entirely.
     * Used only by the key rotation command: re-encrypting a value under a new
     * key is a storage-format change, not a business change, so it must not
     * emit ConfigurationValueChanged or write a history row.
     */
    public function rotateEncryptedValue(ConfigurationId $id, string $ciphertext, int $keyVersion): void;
}
