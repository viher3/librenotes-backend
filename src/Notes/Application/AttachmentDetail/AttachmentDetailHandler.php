<?php

declare(strict_types=1);

namespace App\Notes\Application\AttachmentDetail;

use App\Core\Application\Query\QueryHandler;
use App\Notes\Domain\Exception\AttachmentNotFound;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\ValueObject\OwnerId;

class AttachmentDetailHandler implements QueryHandler
{
    public function __construct(
        private AttachmentRepository $attachmentRepository
    ) {
    }

    public function __invoke(AttachmentDetailQuery $query): AttachmentDetailResponse
    {
        $ownerId = new OwnerId($query->ownerId);
        $attachment = $this->attachmentRepository->getById($query->id);

        if ($attachment === null || !$attachment->belongsTo($ownerId)) {
            throw new AttachmentNotFound($query->id);
        }

        return new AttachmentDetailResponse($attachment);
    }
}
