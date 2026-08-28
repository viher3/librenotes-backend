<?php

declare(strict_types=1);

namespace App\Notes\Application\PermanentlyDeleteAttachment;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Notes\Domain\Exception\AttachmentNotFound;
use App\Notes\Domain\Exception\NotInTrash;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Service\FileStoragePort;
use App\Notes\Domain\ValueObject\OwnerId;

class PermanentlyDeleteAttachmentHandler implements TransactionalCommandHandler
{
    public function __construct(
        private AttachmentRepository $attachmentRepository,
        private FileStoragePort $fileStorage
    ) {
    }

    public function __invoke(PermanentlyDeleteAttachmentCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $attachment = $this->attachmentRepository->getById($command->id);

        if ($attachment === null || !$attachment->belongsTo($ownerId)) {
            throw new AttachmentNotFound($command->id);
        }

        if (!$attachment->isDeleted()) {
            throw new NotInTrash($command->id);
        }

        $this->fileStorage->delete($attachment->getStorageKey()->value());
        $this->attachmentRepository->delete($attachment);
    }
}
