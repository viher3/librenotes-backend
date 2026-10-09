<?php

declare(strict_types=1);

namespace App\Notes\Domain\Model;

use App\Core\Domain\Aggregate\AggregateRoot;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\LinkId;
use App\Notes\Domain\ValueObject\LinkNote;
use App\Notes\Domain\ValueObject\LinkTitle;
use App\Notes\Domain\ValueObject\LinkUrl;
use App\Notes\Domain\ValueObject\OwnerId;
use App\Notes\Domain\ValueObject\Tags;

class Link extends AggregateRoot
{
    private const VALID_PARTIAL_UPDATE_FIELDS = [
        'title',
        'url',
        'note',
        'tags',
    ];

    private LinkId $id;
    protected LinkTitle $title;
    protected LinkUrl $url;
    protected ?LinkNote $note;
    protected Tags $tags;
    private ?FolderId $folderId;
    private OwnerId $ownerId;
    private DateTimeValueObject $createdAt;
    private DateTimeValueObject $updatedAt;
    private ?DateTimeValueObject $deletedAt = null;

    public function __construct(
        LinkId $id,
        LinkTitle $title,
        LinkUrl $url,
        ?LinkNote $note,
        ?FolderId $folderId,
        OwnerId $ownerId,
        \DateTimeInterface $createdAt,
        ?Tags $tags = null
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->url = $url;
        $this->note = $note;
        $this->tags = $tags ?? Tags::empty();
        $this->folderId = $folderId;
        $this->ownerId = $ownerId;
        $this->createdAt = new DateTimeValueObject($createdAt);
        $this->updatedAt = new DateTimeValueObject($createdAt);
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function update(array $fields, \DateTimeInterface $now): void
    {
        $this->partialUpdate(self::VALID_PARTIAL_UPDATE_FIELDS, $fields);
        $this->updatedAt = new DateTimeValueObject($now);
    }

    protected function castPartialUpdateValue(string $field, mixed $value): mixed
    {
        return match ($field) {
            'title' => new LinkTitle($value),
            'url' => new LinkUrl($value),
            'note' => ($value === null || $value === '') ? null : new LinkNote($value),
            'tags' => new Tags($value),
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

    public function getId(): LinkId
    {
        return $this->id;
    }

    public function getTitle(): LinkTitle
    {
        return $this->title;
    }

    public function getUrl(): LinkUrl
    {
        return $this->url;
    }

    public function getNote(): ?LinkNote
    {
        return $this->note;
    }

    public function getTags(): Tags
    {
        return $this->tags;
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
