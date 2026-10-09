<?php

declare(strict_types=1);

namespace App\Notes\Application\LinkDetail;

use App\Core\Application\Query\QueryResponse;
use App\Notes\Domain\Model\Link;
use App\Shared\Domain\Time\Date;

class LinkDetailResponse implements QueryResponse
{
    public function __construct(
        private Link $link
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getResult(): array
    {
        return [
            'id' => $this->link->getId()->value(),
            'title' => $this->link->getTitle()->value(),
            'url' => $this->link->getUrl()->value(),
            'note' => $this->link->getNote()?->value(),
            'folder_id' => $this->link->getFolderId()?->value(),
            'tags' => $this->link->getTags()->names(),
            'created_at' => $this->link->getCreatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
            'updated_at' => $this->link->getUpdatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
        ];
    }
}
