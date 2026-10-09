<?php

declare(strict_types=1);

namespace App\Notes\Domain\Service;

use App\Notes\Domain\Exception\NoteCycleDetected;
use App\Notes\Domain\Model\Folder;
use App\Notes\Domain\Model\Link;
use App\Notes\Domain\Model\Note;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\NoteId;

/**
 * Where a note sits in the tree: which ancestors it has, and whether a move would break the tree.
 * A note lives in a folder (which may itself be nested), under another note, or at the root.
 */
class NoteHierarchy
{
    /** Guards against corrupt data: a legitimate tree is never this deep. */
    private const int MAX_DEPTH = 100;

    public function __construct(
        private NoteRepository $noteRepository,
        private FolderRepository $folderRepository
    ) {
    }

    /**
     * A note cannot be placed under itself or under any of its own descendants.
     *
     * @throws NoteCycleDetected
     */
    public function assertCanBePlacedUnder(Note $note, NoteId $target): void
    {
        $current = $target;
        for ($depth = 0; $depth < self::MAX_DEPTH; $depth++) {
            if ($current->equals($note->getId())) {
                throw new NoteCycleDetected($note->getId()->value(), $target->value());
            }
            $parent = $this->noteRepository->getById($current->value());
            $next = $parent?->getParentNoteId();
            if ($next === null) {
                return;
            }
            $current = $next;
        }
    }

    /**
     * Ancestors of a note from the root down to its direct parent (the note itself is not included).
     *
     * @return list<array{type: string, id: string, title: string}>
     */
    public function pathOfNote(Note $note): array
    {
        $path = [];
        $current = $note;
        for ($depth = 0; $depth < self::MAX_DEPTH; $depth++) {
            if ($current->getParentNoteId() !== null) {
                $parent = $this->noteRepository->getById($current->getParentNoteId()->value());
                if ($parent === null) {
                    break;
                }
                array_unshift($path, ['type' => 'note', 'id' => $parent->getId()->value(), 'title' => $parent->getTitle()->value()]);
                $current = $parent;
                continue;
            }
            if ($current->getFolderId() !== null) {
                $folder = $this->folderRepository->getById($current->getFolderId()->value());
                if ($folder !== null) {
                    $path = [...$this->folderChain($folder), ...$path];
                }
            }
            break;
        }

        return $path;
    }

    /**
     * Ancestors of a link from the root down to its direct parent (the link itself is not included): the note it is
     * under, that note's own ancestors, or the chain of folders it is in.
     *
     * @return list<array{type: string, id: string, title: string}>
     */
    public function pathOfLink(Link $link): array
    {
        if ($link->getParentNoteId() !== null) {
            $parent = $this->noteRepository->getById($link->getParentNoteId()->value());

            return $parent === null
                ? []
                : [
                    ...$this->pathOfNote($parent),
                    ['type' => 'note', 'id' => $parent->getId()->value(), 'title' => $parent->getTitle()->value()],
                ];
        }

        if ($link->getFolderId() !== null) {
            $folder = $this->folderRepository->getById($link->getFolderId()->value());

            return $folder === null ? [] : $this->folderChain($folder);
        }

        return [];
    }

    /**
     * Ancestors of a folder from the root down to its direct parent (the folder itself is not included).
     *
     * @return list<array{type: string, id: string, title: string}>
     */
    public function pathOfFolder(Folder $folder): array
    {
        $parentId = $folder->getParentFolderId();
        $parent = $parentId !== null ? $this->folderRepository->getById($parentId->value()) : null;

        return $parent !== null ? $this->folderChain($parent) : [];
    }

    /**
     * @return list<array{type: string, id: string, title: string}> the folder and its ancestors, root first
     */
    private function folderChain(Folder $folder): array
    {
        $chain = [];
        $current = $folder;
        for ($depth = 0; $depth < self::MAX_DEPTH; $depth++) {
            array_unshift($chain, ['type' => 'folder', 'id' => $current->getId()->value(), 'title' => $current->getName()->value()]);
            $parentId = $current->getParentFolderId();
            $parent = $parentId instanceof FolderId ? $this->folderRepository->getById($parentId->value()) : null;
            if ($parent === null) {
                break;
            }
            $current = $parent;
        }

        return $chain;
    }
}
