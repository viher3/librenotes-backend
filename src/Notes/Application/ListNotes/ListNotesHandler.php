<?php

declare(strict_types=1);

namespace App\Notes\Application\ListNotes;

use App\Core\Application\Query\QueryHandler;
use App\Notes\Domain\Repository\NoteListCriteria;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\OwnerId;
use App\Notes\Domain\ValueObject\Tags;

class ListNotesHandler implements QueryHandler
{
    public function __construct(
        private NoteRepository $noteRepository
    ) {
    }

    public function __invoke(ListNotesQuery $query): ListNotesResponse
    {
        $size = $query->size ?? NoteListCriteria::DEFAULT_SIZE;
        $page = max(1, $query->page ?? 1);

        $criteria = new NoteListCriteria(
            ownerId: new OwnerId($query->ownerId),
            folderId: $query->folderId !== null ? new FolderId($query->folderId) : null,
            rootOnly: $query->rootOnly,
            tag: $query->tag !== null ? Tags::normalize($query->tag) : null,
            pinned: $query->pinned,
            orderBy: $query->orderBy ?? 'updated_at',
            orderDirection: $query->orderDirection ?? 'desc',
            page: $page,
            size: $size
        );

        $result = $this->noteRepository->findPage($criteria);

        return new ListNotesResponse(
            $result['notes'],
            $result['total'],
            $page,
            max(1, min($size, NoteListCriteria::MAX_SIZE))
        );
    }
}
