<?php

declare(strict_types=1);

namespace App\Notes\Domain\Model;

use App\Core\Domain\Aggregate\AggregateRoot;
use App\Core\Domain\ValueObject\BoolValueObject;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Notes\Domain\Exception\InvalidContentLocation;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\Icon;
use App\Notes\Domain\ValueObject\NoteContent;
use App\Notes\Domain\ValueObject\NoteId;
use App\Notes\Domain\ValueObject\NoteTitle;
use App\Notes\Domain\ValueObject\OwnerId;
use App\Notes\Domain\ValueObject\Tags;

class Note extends AggregateRoot
{
    private const VALID_PARTIAL_UPDATE_FIELDS = [
        'title',
        'icon',
        'content',
        'pinned',
        'tags',
    ];

    private NoteId $id;
    protected NoteTitle $title;
    protected ?Icon $icon;
    protected NoteContent $content;
    private ?FolderId $folderId;
    private ?NoteId $parentNoteId;
    private OwnerId $ownerId;
    protected BoolValueObject $pinned;
    protected Tags $tags;
    private DateTimeValueObject $createdAt;
    private DateTimeValueObject $updatedAt;
    private ?DateTimeValueObject $deletedAt = null;

    public function __construct(
        NoteId $id,
        NoteTitle $title,
        NoteContent $content,
        ?FolderId $folderId,
        OwnerId $ownerId,
        \DateTimeInterface $createdAt,
        bool $pinned = false,
        ?Tags $tags = null,
        ?NoteId $parentNoteId = null,
        ?Icon $icon = null
    ) {
        if ($folderId !== null && $parentNoteId !== null) {
            throw new InvalidContentLocation();
        }

        $this->id = $id;
        $this->title = $title;
        $this->icon = $icon;
        $this->content = $content;
        $this->folderId = $folderId;
        $this->parentNoteId = $parentNoteId;
        $this->ownerId = $ownerId;
        $this->pinned = new BoolValueObject($pinned);
        $this->tags = $tags ?? Tags::empty();
        $this->createdAt = new DateTimeValueObject($createdAt);
        $this->updatedAt = new DateTimeValueObject($createdAt);
    }

    public function update(array $fields, \DateTimeInterface $now): void
    {
        $this->partialUpdate(self::VALID_PARTIAL_UPDATE_FIELDS, $fields);
        $this->updatedAt = new DateTimeValueObject($now);
    }

    protected function castPartialUpdateValue(string $field, mixed $value): mixed
    {
        return match ($field) {
            'title' => new NoteTitle($value),
            'icon' => Icon::fromNullable($value),
            'content' => new NoteContent($value),
            'pinned' => new BoolValueObject($value),
            'tags' => new Tags($value),
            default => $value,
        };
    }

    /**
     * Places the note in a folder, under another note, or at the root (both null).
     */
    public function moveTo(?FolderId $folderId, ?NoteId $parentNoteId, \DateTimeInterface $now): void
    {
        if ($folderId !== null && $parentNoteId !== null) {
            throw new InvalidContentLocation();
        }

        $this->folderId = $folderId;
        $this->parentNoteId = $parentNoteId;
        $this->updatedAt = new DateTimeValueObject($now);
    }

    public function belongsTo(OwnerId $ownerId): bool
    {
        return $this->ownerId->equals($ownerId);
    }

    public function moveToTrash(\DateTimeInterface $now): void
    {
        if ($this->deletedAt !== null) {
            return;
        }

        $this->deletedAt = new DateTimeValueObject($now);
    }

    public function restore(): void
    {
        $this->deletedAt = null;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function getId(): NoteId
    {
        return $this->id;
    }

    public function getTitle(): NoteTitle
    {
        return $this->title;
    }

    public function getIcon(): ?Icon
    {
        return $this->icon;
    }

    public function getContent(): NoteContent
    {
        return $this->content;
    }

    public function isPinned(): bool
    {
        return $this->pinned->value();
    }

    public function getTags(): Tags
    {
        return $this->tags;
    }

    public function getFolderId(): ?FolderId
    {
        return $this->folderId;
    }

    public function getParentNoteId(): ?NoteId
    {
        return $this->parentNoteId;
    }

    public function getOwnerId(): OwnerId
    {
        return $this->ownerId;
    }

    public function getCreatedAt(): DateTimeValueObject
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeValueObject
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?DateTimeValueObject
    {
        return $this->deletedAt;
    }
}
