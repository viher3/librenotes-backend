<?php

declare(strict_types=1);

namespace App\Notes\Application\AttachmentContent;

use App\Core\Application\Query\QueryHandler;
use App\Notes\Domain\Exception\AttachmentNotFound;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Service\FileStoragePort;
use App\Notes\Domain\ValueObject\OwnerId;

class AttachmentContentHandler implements QueryHandler
{
    public function __construct(
        private AttachmentRepository $attachmentRepository,
        private FileStoragePort $fileStorage
    ) {
    }

    public function __invoke(AttachmentContentQuery $query): AttachmentContentResponse
    {
        $ownerId = new OwnerId($query->ownerId);
        $attachment = $this->attachmentRepository->getById($query->id);

        if ($attachment === null || !$attachment->belongsTo($ownerId) || $attachment->isDeleted()) {
            throw new AttachmentNotFound($query->id);
        }

        return new AttachmentContentResponse(
            $attachment,
            $this->fileStorage->read($attachment->getStorageKey()->value())
        );
    }
}
