<?php

declare(strict_types=1);

namespace App\Notes\Application\CreateLink;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Model\Link;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\LinkId;
use App\Notes\Domain\ValueObject\LinkNote;
use App\Notes\Domain\ValueObject\LinkTitle;
use App\Notes\Domain\ValueObject\LinkUrl;
use App\Notes\Domain\ValueObject\OwnerId;
use App\Notes\Domain\ValueObject\Tags;

class CreateLinkHandler implements TransactionalCommandHandler
{
    public function __construct(
        private LinkRepository $linkRepository,
        private FolderRepository $folderRepository,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(CreateLinkCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $folderId = null;

        if ($command->folderId !== null) {
            $folderId = new FolderId($command->folderId);
            $folder = $this->folderRepository->getById($folderId->value());

            if ($folder === null || !$folder->belongsTo($ownerId) || $folder->isDeleted()) {
                throw new FolderNotFound($command->folderId);
            }
        }

        $this->linkRepository->save(
            new Link(
                id: new LinkId($command->id),
                title: new LinkTitle($command->title),
                url: new LinkUrl($command->url),
                note: ($command->note === null || $command->note === '') ? null : new LinkNote($command->note),
                folderId: $folderId,
                ownerId: $ownerId,
                createdAt: $this->dateTimeProvider->now(),
                tags: new Tags($command->tags)
            )
        );
    }
}
