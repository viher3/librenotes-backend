<?php

declare(strict_types=1);

namespace App\Notes\Domain\Repository;

interface SearchRepository
{
    /**
     * Matches the owner's notes (title, content) and links (title, note, url) that are not in trash.
     * `snippet` is a plain-text excerpt around the first match.
     *
     * @return array{
     *     items: list<array{type: string, id: string, title: string, snippet: string, folder_id: ?string, tags: string[], updated_at: string, url: ?string}>,
     *     total: int
     * }
     */
    public function search(SearchCriteria $criteria): array;
}
