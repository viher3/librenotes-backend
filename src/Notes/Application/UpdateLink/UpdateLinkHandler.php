<?php

declare(strict_types=1);

namespace App\Notes\Application\UpdateLink;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Domain\Exception\LinkNotFound;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\ValueObject\OwnerId;

class UpdateLinkHandler implements TransactionalCommandHandler
{
    public function __construct(
        private LinkRepository $linkRepository,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(UpdateLinkCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $link = $this->linkRepository->getById($command->id);

        if ($link === null || !$link->belongsTo($ownerId) || $link->isDeleted()) {
            throw new LinkNotFound($command->id);
        }

        $link->update($command->fields, $this->dateTimeProvider->now());
        $this->linkRepository->save($link);
    }
}
