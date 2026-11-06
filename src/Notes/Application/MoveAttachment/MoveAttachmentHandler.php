<?php

declare(strict_types=1);

namespace App\Notes\Application\MoveAttachment;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Notes\Domain\Exception\AttachmentNotFound;
use App\Notes\Domain\Exception\InvalidAttachmentLocation;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Service\ContentPlacement;
use App\Notes\Domain\ValueObject\OwnerId;

/** Moves a file into a folder or under a note. A file never lives at the top level. */
class MoveAttachmentHandler implements TransactionalCommandHandler
{
    public function __construct(
        private AttachmentRepository $attachmentRepository,
        private ContentPlacement $placement
    ) {
    }

    public function __invoke(MoveAttachmentCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $attachment = $this->attachmentRepository->getById($command->id);

        if ($attachment === null || !$attachment->belongsTo($ownerId) || $attachment->isDeleted()) {
            throw new AttachmentNotFound($command->id);
        }

        [$folderId, $noteId] = $this->placement->resolve($command->folderId, $command->noteId, $ownerId);
        if ($folderId === null && $noteId === null) {
            throw new InvalidAttachmentLocation();
        }

        $attachment->moveTo($folderId, $noteId);

        $this->attachmentRepository->save($attachment);
    }
}
