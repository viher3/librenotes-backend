<?php

declare(strict_types=1);

namespace App\Notes\Application\MoveAttachmentToTrash;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Domain\Exception\AttachmentNotFound;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\ValueObject\OwnerId;

class MoveAttachmentToTrashHandler implements TransactionalCommandHandler
{
    public function __construct(
        private AttachmentRepository $attachmentRepository,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(MoveAttachmentToTrashCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $attachment = $this->attachmentRepository->getById($command->id);

        if ($attachment === null || !$attachment->belongsTo($ownerId)) {
            throw new AttachmentNotFound($command->id);
        }

        $attachment->moveToTrash($this->dateTimeProvider->now());

        $this->attachmentRepository->save($attachment);
    }
}
