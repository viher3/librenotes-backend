<?php

declare(strict_types=1);

namespace App\Notes\Application\MoveLinkToTrash;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Domain\Exception\LinkNotFound;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\ValueObject\OwnerId;

class MoveLinkToTrashHandler implements TransactionalCommandHandler
{
    public function __construct(
        private LinkRepository $linkRepository,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(MoveLinkToTrashCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $link = $this->linkRepository->getById($command->id);

        if ($link === null || !$link->belongsTo($ownerId)) {
            throw new LinkNotFound($command->id);
        }

        $link->moveToTrash($this->dateTimeProvider->now());
        $this->linkRepository->save($link);
    }
}
