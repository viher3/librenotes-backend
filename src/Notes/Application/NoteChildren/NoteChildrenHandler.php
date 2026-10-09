<?php

declare(strict_types=1);

namespace App\Notes\Application\NoteChildren;

use App\Core\Application\Query\QueryHandler;
use App\Notes\Domain\Exception\NoteNotFound;
use App\Notes\Domain\Model\Note;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\OwnerId;

class NoteChildrenHandler implements QueryHandler
{
    public function __construct(
        private NoteRepository $noteRepository,
        private LinkRepository $linkRepository
    ) {
    }

    public function __invoke(NoteChildrenQuery $query): NoteChildrenResponse
    {
        $ownerId = new OwnerId($query->ownerId);
        $note = $this->noteRepository->getById($query->id);

        if ($note === null || !$note->belongsTo($ownerId) || $note->isDeleted()) {
            throw new NoteNotFound($query->id);
        }

        $children = $this->noteRepository->findByParent($note->getId(), $ownerId);

        return new NoteChildrenResponse(
            $children,
            $this->noteRepository->countChildren($ownerId, array_map(static fn (Note $n) => $n->getId()->value(), $children)),
            $this->linkRepository->findByParent($note->getId(), $ownerId)
        );
    }
}
