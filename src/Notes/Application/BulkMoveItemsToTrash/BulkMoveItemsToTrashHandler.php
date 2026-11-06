<?php

declare(strict_types=1);

namespace App\Notes\Application\BulkMoveItemsToTrash;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Application\BulkItems\BulkItemsResponse;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\Service\FolderTreeLifecycle;
use App\Notes\Domain\Service\ItemSelection;
use App\Notes\Domain\Service\NoteTreeLifecycle;
use App\Notes\Domain\ValueObject\OwnerId;

/**
 * Sends many folders, notes, links and files to the trash at once. Everything is validated before anything
 * changes, and the whole thing is one transaction: it either all happens or none of it does.
 */
class BulkMoveItemsToTrashHandler implements TransactionalCommandHandler
{
    public function __construct(
        private ItemSelection $selection,
        private FolderTreeLifecycle $folderTree,
        private NoteTreeLifecycle $noteTree,
        private NoteRepository $noteRepository,
        private LinkRepository $linkRepository,
        private AttachmentRepository $attachmentRepository,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(BulkMoveItemsToTrashCommand $command): BulkItemsResponse
    {
        $ownerId = new OwnerId($command->ownerId);
        $items = $this->selection->select($command->items, $ownerId);
        $now = $this->dateTimeProvider->now();

        foreach ($items->folders as $folder) {
            $this->folderTree->trash($folder, $ownerId, $now);
        }
        foreach ($items->notes as $note) {
            $note->moveToTrash($now);
            $this->noteRepository->save($note);
            $this->noteTree->trash($note, $ownerId, $now);
        }
        foreach ($items->links as $link) {
            $link->moveToTrash($now);
            $this->linkRepository->save($link);
        }
        foreach ($items->attachments as $attachment) {
            $attachment->moveToTrash($now);
            $this->attachmentRepository->save($attachment);
        }

        return new BulkItemsResponse($items->count());
    }
}
