<?php

declare(strict_types=1);

namespace App\Notes\Application\NoteDetail;

use App\Core\Application\Query\QueryHandler;
use App\Notes\Domain\Exception\NoteNotFound;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\Service\NoteHierarchy;
use App\Notes\Domain\ValueObject\OwnerId;

class NoteDetailHandler implements QueryHandler
{
    public function __construct(
        private NoteRepository $noteRepository,
        private AttachmentRepository $attachmentRepository,
        private NoteHierarchy $hierarchy
    ) {
    }

    public function __invoke(NoteDetailQuery $query): NoteDetailResponse
    {
        $ownerId = new OwnerId($query->ownerId);
        $note = $this->noteRepository->getById($query->id);

        if ($note === null || !$note->belongsTo($ownerId)) {
            throw new NoteNotFound($query->id);
        }

        return new NoteDetailResponse(
            $note,
            $this->attachmentRepository->findByNote($note->getId(), $ownerId),
            $this->hierarchy->pathOfNote($note),
            $this->noteRepository->countChildren($ownerId, [$note->getId()->value()])[$note->getId()->value()] ?? 0
        );
    }
}
