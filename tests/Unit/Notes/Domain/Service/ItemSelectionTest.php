<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notes\Domain\Service;

use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Exception\NoteNotFound;
use App\Notes\Domain\Model\Folder;
use App\Notes\Domain\Model\Note;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\Service\ItemSelection;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\FolderName;
use App\Notes\Domain\ValueObject\ItemRef;
use App\Notes\Domain\ValueObject\ItemType;
use App\Notes\Domain\ValueObject\NoteContent;
use App\Notes\Domain\ValueObject\NoteId;
use App\Notes\Domain\ValueObject\NoteTitle;
use App\Notes\Domain\ValueObject\OwnerId;
use PHPUnit\Framework\TestCase;

class ItemSelectionTest extends TestCase
{
    private OwnerId $owner;
    /** @var array<string, Folder> */
    private array $folders = [];
    /** @var array<string, Note> */
    private array $notes = [];
    private ItemSelection $selection;

    protected function setUp(): void
    {
        $this->owner = OwnerId::random();
        $folderRepository = $this->createStub(FolderRepository::class);
        $folderRepository->method('getById')->willReturnCallback(fn (string $id) => $this->folders[$id] ?? null);
        $noteRepository = $this->createStub(NoteRepository::class);
        $noteRepository->method('getById')->willReturnCallback(fn (string $id) => $this->notes[$id] ?? null);

        $this->selection = new ItemSelection(
            $folderRepository,
            $noteRepository,
            $this->createStub(LinkRepository::class),
            $this->createStub(AttachmentRepository::class)
        );
    }

    private function folder(?Folder $parent = null, ?OwnerId $owner = null): Folder
    {
        $folder = new Folder(FolderId::random(), new FolderName('F'), $parent?->getId(), $owner ?? $this->owner, new \DateTimeImmutable());

        return $this->folders[$folder->getId()->value()] = $folder;
    }

    private function note(?Folder $folder = null, ?Note $parent = null): Note
    {
        $note = new Note(
            NoteId::random(),
            new NoteTitle('N'),
            new NoteContent(''),
            $folder?->getId(),
            $this->owner,
            new \DateTimeImmutable(),
            parentNoteId: $parent?->getId()
        );

        return $this->notes[$note->getId()->value()] = $note;
    }

    private function ref(ItemType $type, object $item): ItemRef
    {
        return new ItemRef($type, $item->getId()->value());
    }

    public function testItemsInsideOtherSelectedItemsAreLeftOut(): void
    {
        $outer = $this->folder();
        $inner = $this->folder($outer);
        $inFolder = $this->note($inner);
        $parent = $this->note($outer);
        $child = $this->note(null, $parent);
        $alone = $this->note();

        $result = $this->selection->select([
            $this->ref(ItemType::Note, $child),
            $this->ref(ItemType::Note, $inFolder),
            $this->ref(ItemType::Folder, $inner),
            $this->ref(ItemType::Folder, $outer),
            $this->ref(ItemType::Note, $parent),
            $this->ref(ItemType::Note, $alone),
        ], $this->owner);

        $this->assertSame([$outer], $result->folders);
        $this->assertSame([$alone], $result->notes);
        $this->assertSame(2, $result->count());
    }

    public function testASelectedChildStaysWhenOnlyItsSiblingIsSelected(): void
    {
        $folder = $this->folder();
        $parent = $this->note($folder);
        $child = $this->note(null, $parent);
        $sibling = $this->note($folder);

        $result = $this->selection->select([
            $this->ref(ItemType::Note, $child),
            $this->ref(ItemType::Note, $sibling),
        ], $this->owner);

        $this->assertSame([$child, $sibling], $result->notes);
    }

    public function testAnItemOfAnotherUserIsNotFound(): void
    {
        $foreign = $this->folder(null, OwnerId::random());

        $this->expectException(FolderNotFound::class);

        $this->selection->select([$this->ref(ItemType::Folder, $foreign)], $this->owner);
    }

    public function testATrashedOrUnknownItemIsNotFound(): void
    {
        $note = $this->note();
        $note->moveToTrash(new \DateTimeImmutable());

        $this->expectException(NoteNotFound::class);

        $this->selection->select([$this->ref(ItemType::Note, $note)], $this->owner);
    }
}
