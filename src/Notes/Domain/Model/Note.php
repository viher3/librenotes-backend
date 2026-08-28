<?php

declare(strict_types=1);

namespace App\Notes\Domain\Model;

use App\Core\Domain\Aggregate\AggregateRoot;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\NoteContent;
use App\Notes\Domain\ValueObject\NoteId;
use App\Notes\Domain\ValueObject\NoteTitle;
use App\Notes\Domain\ValueObject\OwnerId;

class Note extends AggregateRoot
{
    private const VALID_PARTIAL_UPDATE_FIELDS = [
        'title',
        'content',
    ];

    private NoteId $id;
    private NoteTitle $title;
    private NoteContent $content;
    private ?FolderId $folderId;
    private OwnerId $ownerId;
    private DateTimeValueObject $createdAt;
    private DateTimeValueObject $updatedAt;
    private ?DateTimeValueObject $deletedAt = null;

    public function __construct(
        NoteId $id,
        NoteTitle $title,
        NoteContent $content,
        ?FolderId $folderId,
        OwnerId $ownerId,
        \DateTimeInterface $createdAt
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->content = $content;
        $this->folderId = $folderId;
        $this->ownerId = $ownerId;
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
            'content' => new NoteContent($value),
            default => $value,
        };
    }

    public function moveTo(?FolderId $folderId, \DateTimeInterface $now): void
    {
        $this->folderId = $folderId;
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

    public function getContent(): NoteContent
    {
        return $this->content;
    }

    public function getFolderId(): ?FolderId
    {
        return $this->folderId;
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
