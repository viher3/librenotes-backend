<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Persistence\Doctrine\Repository;

use App\Notes\Domain\Repository\SearchCriteria;
use App\Notes\Domain\Repository\SearchRepository;
use Doctrine\DBAL\Connection;

/**
 * Substring search (case- and accent-insensitive through the table collation). It deliberately does not use
 * MySQL FULLTEXT: that ignores short words and stopwords, which is surprising for find-as-you-type.
 * Cost is bounded by the per-owner row set; revisit when a single user can own very large volumes.
 */
final class DbalSearchRepository implements SearchRepository
{
    private const int SNIPPET_LENGTH = 160;
    private const int SNIPPET_LEAD = 50;
    /** Notes encrypted by the client hold ciphertext: it is never matched nor excerpted (see NoteContent). */
    private const string ENCRYPTED_CONTENT = "n.content LIKE 'lnenc:%'";

    public function __construct(
        private Connection $connection
    ) {
    }

    public function search(SearchCriteria $criteria): array
    {
        // The first term (unwrapped) locates the match that the snippet is built around.
        $params = ['owner' => $criteria->ownerId->value(), 'raw_term' => $criteria->terms[0] ?? ''];
        $selects = [];

        if (in_array(SearchCriteria::TYPE_NOTE, $criteria->types, true)) {
            $noteBody = 'CASE WHEN ' . self::ENCRYPTED_CONTENT . " THEN '' ELSE n.content END";
            $selects[] = $this->select(
                type: 'note',
                table: 'note',
                alias: 'n',
                titleColumn: 'n.title',
                bodyExpr: $noteBody,
                urlExpr: 'NULL',
                encryptedExpr: self::ENCRYPTED_CONTENT,
                matchColumns: ['n.title', $noteBody],
                criteria: $criteria,
                params: $params
            );
        }
        if (in_array(SearchCriteria::TYPE_LINK, $criteria->types, true)) {
            $selects[] = $this->select(
                type: 'link',
                table: 'link',
                alias: 'l',
                titleColumn: 'l.title',
                bodyExpr: "COALESCE(l.note, '')",
                urlExpr: 'l.url',
                encryptedExpr: '0',
                matchColumns: ['l.title', 'l.note', 'l.url'],
                criteria: $criteria,
                params: $params
            );
        }

        if ($selects === []) {
            return ['items' => [], 'total' => 0];
        }

        $union = implode(' UNION ALL ', $selects);
        $total = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM ($union) r", $params);

        $size = max(1, $criteria->size);
        $offset = (max(1, $criteria->page) - 1) * $size;
        $rows = $this->connection->fetchAllAssociative(
            "SELECT * FROM ($union) r ORDER BY r.title_rank ASC, r.updated_at DESC, r.id ASC LIMIT $size OFFSET $offset",
            $params
        );

        return [
            'items' => array_map(fn (array $row): array => $this->toItem($row, $criteria), $rows),
            'total' => $total,
        ];
    }

    /**
     * @param string[] $matchColumns
     * @param array<string, mixed> $params
     */
    private function select(
        string $type,
        string $table,
        string $alias,
        string $titleColumn,
        string $bodyExpr,
        string $urlExpr,
        string $encryptedExpr,
        array $matchColumns,
        SearchCriteria $criteria,
        array &$params
    ): string {
        $where = ["$alias.owner_id = :owner", "$alias.deleted_at IS NULL"];
        $titleHits = [];

        foreach ($criteria->terms as $i => $term) {
            $key = "term_$i";
            $params[$key] = '%' . addcslashes($term, '\\%_') . '%';
            $any = array_map(static fn (string $column): string => "$column LIKE :$key", $matchColumns);
            $where[] = '(' . implode(' OR ', $any) . ')';
            $titleHits[] = "$titleColumn LIKE :$key";
        }

        if ($criteria->tag !== null) {
            $where[] = "JSON_CONTAINS($alias.tags, JSON_QUOTE(:tag))";
            $params['tag'] = $criteria->tag;
        }

        $titleRank = '(CASE WHEN ' . implode(' AND ', $titleHits) . ' THEN 0 ELSE 1 END)';
        $whereSql = implode(' AND ', $where);

        return "(SELECT '$type' AS type, $alias.id AS id, $titleColumn AS title, $alias.icon AS icon, $alias.folder_id AS folder_id, "
            . "$alias.tags AS tags, $alias.updated_at AS updated_at, $urlExpr AS url, $titleRank AS title_rank, "
            . "($encryptedExpr) AS is_encrypted, "
            . "LOCATE(:raw_term, $bodyExpr) AS pos, "
            . 'SUBSTRING(' . $bodyExpr . ', GREATEST(1, LOCATE(:raw_term, ' . $bodyExpr . ') - ' . self::SNIPPET_LEAD . '), ' . self::SNIPPET_LENGTH . ') AS body_excerpt, '
            . "LEFT($bodyExpr, " . self::SNIPPET_LENGTH . ") AS body_head "
            . "FROM $table $alias WHERE $whereSql)";
    }

    /**
     * @param array<string, mixed> $row
     * @return array{type: string, id: string, title: string, icon: ?string, snippet: string, folder_id: ?string, tags: string[], updated_at: string, url: ?string, is_encrypted: bool}
     */
    private function toItem(array $row, SearchCriteria $criteria): array
    {
        $hasBodyMatch = (int) $row['pos'] > 0;
        $source = $hasBodyMatch ? (string) $row['body_excerpt'] : (string) $row['body_head'];
        $snippet = trim((string) preg_replace('/\s+/u', ' ', $source));

        if ($snippet !== '') {
            if ($hasBodyMatch && (int) $row['pos'] > self::SNIPPET_LEAD + 1) {
                $snippet = '…' . $snippet;
            }
            if (mb_strlen($snippet) >= self::SNIPPET_LENGTH - 1) {
                $snippet .= '…';
            }
        }

        $tags = $row['tags'] !== null ? json_decode((string) $row['tags'], true) : [];

        return [
            'type' => (string) $row['type'],
            'id' => (string) $row['id'],
            'title' => (string) $row['title'],
            'icon' => $row['icon'] !== null ? (string) $row['icon'] : null,
            'snippet' => $snippet,
            'folder_id' => $row['folder_id'] !== null ? (string) $row['folder_id'] : null,
            'tags' => is_array($tags) ? array_values($tags) : [],
            'updated_at' => (new \DateTimeImmutable((string) $row['updated_at']))->format(\DATE_ATOM),
            'url' => $row['url'] !== null ? (string) $row['url'] : null,
            'is_encrypted' => (int) $row['is_encrypted'] === 1,
        ];
    }
}
