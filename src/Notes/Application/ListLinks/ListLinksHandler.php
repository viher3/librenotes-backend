<?php

declare(strict_types=1);

namespace App\Notes\Application\ListLinks;

use App\Core\Application\Query\QueryHandler;
use App\Notes\Domain\Repository\LinkListCriteria;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\Repository\NoteListCriteria;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\NoteId;
use App\Notes\Domain\ValueObject\OwnerId;
use App\Notes\Domain\ValueObject\Tags;

class ListLinksHandler implements QueryHandler
{
    public function __construct(
        private LinkRepository $linkRepository
    ) {
    }

    public function __invoke(ListLinksQuery $query): ListLinksResponse
    {
        $size = max(1, min($query->size ?? NoteListCriteria::DEFAULT_SIZE, NoteListCriteria::MAX_SIZE));
        $page = max(1, $query->page ?? 1);

        $result = $this->linkRepository->findPage(
            new LinkListCriteria(
                ownerId: new OwnerId($query->ownerId),
                folderId: $query->folderId !== null ? new FolderId($query->folderId) : null,
                rootOnly: $query->rootOnly,
                tag: $query->tag !== null ? Tags::normalize($query->tag) : null,
                orderBy: $query->orderBy ?? 'updated_at',
                orderDirection: $query->orderDirection ?? 'desc',
                page: $page,
                size: $size,
                parentNoteId: $query->parentNoteId !== null ? new NoteId($query->parentNoteId) : null
            )
        );

        return new ListLinksResponse($result['links'], $result['total'], $page, $size);
    }
}
