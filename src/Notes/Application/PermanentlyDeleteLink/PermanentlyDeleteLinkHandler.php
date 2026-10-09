<?php

declare(strict_types=1);

namespace App\Notes\Application\PermanentlyDeleteLink;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Notes\Domain\Exception\LinkNotFound;
use App\Notes\Domain\Exception\NotInTrash;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\ValueObject\OwnerId;

class PermanentlyDeleteLinkHandler implements TransactionalCommandHandler
{
    public function __construct(
        private LinkRepository $linkRepository
    ) {
    }

    public function __invoke(PermanentlyDeleteLinkCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $link = $this->linkRepository->getById($command->id);

        if ($link === null || !$link->belongsTo($ownerId)) {
            throw new LinkNotFound($command->id);
        }

        if (!$link->isDeleted()) {
            throw new NotInTrash($command->id);
        }

        $this->linkRepository->delete($link);
    }
}
