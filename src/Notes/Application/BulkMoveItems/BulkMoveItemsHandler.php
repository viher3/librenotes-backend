<?php

declare(strict_types=1);

namespace App\Notes\Application\BulkMoveItems;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Application\BulkItems\BulkItemsResponse;
use App\Notes\Domain\Exception\DestinationFolderNotFound;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Exception\InvalidAttachmentLocation;
use App\Notes\Domain\Exception\InvalidContentLocation;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\Service\ContentPlacement;
use App\Notes\Domain\Service\ItemSelection;
use App\Notes\Domain\Service\NoteHierarchy;
use App\Notes\Domain\Specification\FolderCanBeMovedSpecificationInterface;
use App\Notes\Domain\ValueObject\OwnerId;

/**
 * Moves many folders, notes, links and files to one destination (a folder, a note, or the top level). The
 * destination and every item are checked against the whole selection before anything changes, and the whole
 * thing is one transaction: it either all happens or none of it does. Files travel with the folder or note
 * they belong to, so they need no change of their own.
 */
class BulkMoveItemsHandler implements TransactionalCommandHandler
{
    public function __construct(
        private ItemSelection $selection,
        private ContentPlacement $placement,
        private NoteHierarchy $hierarchy,
        private FolderCanBeMovedSpecificationInterface $folderCanBeMoved,
        private FolderRepository $folderRepository,
        private NoteRepository $noteRepository,
        private LinkRepository $linkRepository,
        private AttachmentRepository $attachmentRepository,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(BulkMoveItemsCommand $command): BulkItemsResponse
    {
        $ownerId = new OwnerId($command->ownerId);
        $items = $this->selection->select($command->items, $ownerId);

        try {
            [$folderId, $parentNoteId] = $this->placement->resolve($command->folderId, $command->parentNoteId, $ownerId);
        } catch (FolderNotFound) {
            throw new DestinationFolderNotFound((string) $command->folderId);
        }

        // Folders live in folders or at the top level; files need a folder or a note.
        if ($parentNoteId !== null && $items->folders !== []) {
            throw new InvalidContentLocation();
        }
        if ($folderId === null && $parentNoteId === null && $items->attachments !== []) {
            throw new InvalidAttachmentLocation();
        }

        // No item may end up inside itself.
        foreach ($items->folders as $folder) {
            $this->folderCanBeMoved->assertCanBeMoved($folder->getId(), $folderId);
        }
        if ($parentNoteId !== null) {
            foreach ($items->notes as $note) {
                $this->hierarchy->assertCanBePlacedUnder($note, $parentNoteId);
            }
        }

        $now = $this->dateTimeProvider->now();
        foreach ($items->folders as $folder) {
            $folder->moveTo($folderId, $now);
            $this->folderRepository->save($folder);
        }
        foreach ($items->notes as $note) {
            $note->moveTo($folderId, $parentNoteId, $now);
            $this->noteRepository->save($note);
        }
        foreach ($items->links as $link) {
            $link->moveTo($folderId, $parentNoteId, $now);
            $this->linkRepository->save($link);
        }
        foreach ($items->attachments as $attachment) {
            $attachment->moveTo($folderId, $parentNoteId);
            $this->attachmentRepository->save($attachment);
        }

        return new BulkItemsResponse($items->count());
    }
}
