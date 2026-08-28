<?php

declare(strict_types=1);

namespace App\Configuration\Application\History;

use App\Configuration\Domain\Model\ConfigurationHistory;
use App\Core\Application\Query\SearchResponse;

final class ConfigurationHistoryResponse implements SearchResponse
{
    /**
     * @param ConfigurationHistory[] $entries
     */
    public function __construct(
        private readonly array $entries,
        private readonly int $total,
        private readonly int $page,
        private readonly int $perPage
    ) {
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getResult(): array
    {
        $data = [];

        foreach ($this->entries as $entry) {
            $data[] = [
                'action' => $entry->getAction()->value(),
                // Masked the same way live values are: a non-null key_version means
                // that side was sensitive, so it never leaves the server as plaintext.
                'previous_value' => $entry->getPreviousValueKeyVersion() === null
                    ? $entry->getPreviousValue()?->value()
                    : null,
                'new_value' => $entry->getNewValueKeyVersion() === null
                    ? $entry->getNewValue()?->value()
                    : null,
                'changed_by' => $entry->getChangedBy()->value(),
                'changed_at' => $entry->getChangedAt()->value()->format(DATE_ATOM),
            ];
        }

        return [
            'meta' => [
                'page' => $this->page,
                'size' => $this->perPage,
                'total' => $this->total,
                'last_page' => $this->perPage > 0 ? (int) ceil($this->total / $this->perPage) : 0,
            ],
            'data' => $data,
        ];
    }
}
