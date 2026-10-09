<?php

declare(strict_types=1);

namespace App\Notes\Application\MoveLink;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Domain\Exception\LinkNotFound;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\Service\ContentPlacement;
use App\Notes\Domain\ValueObject\OwnerId;

class MoveLinkHandler implements TransactionalCommandHandler
{
    public function __construct(
        private LinkRepository $linkRepository,
        private ContentPlacement $placement,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(MoveLinkCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $link = $this->linkRepository->getById($command->id);

        if ($link === null || !$link->belongsTo($ownerId) || $link->isDeleted()) {
            throw new LinkNotFound($command->id);
        }

        [$folderId, $parentNoteId] = $this->placement->resolve($command->folderId, $command->parentNoteId, $ownerId);

        $link->moveTo($folderId, $parentNoteId, $this->dateTimeProvider->now());
        $this->linkRepository->save($link);
    }
}
