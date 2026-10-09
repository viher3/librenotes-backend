<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Repository;

use App\Notes\Domain\Repository\TagRepository;
use App\Notes\Domain\ValueObject\OwnerId;
use App\Notes\Domain\ValueObject\Tags;
use Doctrine\DBAL\Connection;

final class DbalTagRepository implements TagRepository
{
    public function __construct(
        private Connection $connection
    ) {
    }

    public function countByOwner(OwnerId $ownerId): array
    {
        $maxLength = Tags::MAX_LENGTH;
        $sql = <<<SQL
            SELECT t.name AS name, COUNT(*) AS uses
            FROM (
                SELECT jt.name AS name
                FROM note n, JSON_TABLE(n.tags, '$[*]' COLUMNS (name VARCHAR($maxLength) PATH '$')) jt
                WHERE n.owner_id = :owner AND n.deleted_at IS NULL AND n.tags IS NOT NULL
                UNION ALL
                SELECT jt.name AS name
                FROM link l, JSON_TABLE(l.tags, '$[*]' COLUMNS (name VARCHAR($maxLength) PATH '$')) jt
                WHERE l.owner_id = :owner AND l.deleted_at IS NULL AND l.tags IS NOT NULL
            ) t
            GROUP BY t.name
            ORDER BY uses DESC, t.name ASC
            SQL;

        return array_map(
            static fn (array $row): array => ['name' => (string) $row['name'], 'count' => (int) $row['uses']],
            $this->connection->fetchAllAssociative($sql, ['owner' => $ownerId->value()])
        );
    }
}
