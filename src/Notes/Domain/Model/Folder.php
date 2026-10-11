<?php

declare(strict_types=1);

namespace App\Notes\Domain\Model;

use App\Core\Domain\Aggregate\AggregateRoot;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\FolderName;
use App\Notes\Domain\ValueObject\Icon;
use App\Notes\Domain\ValueObject\OwnerId;

class Folder extends AggregateRoot
{
    private const VALID_PARTIAL_UPDATE_FIELDS = [
        'name',
        'icon',
    ];

    private FolderId $id;
    protected FolderName $name;
    protected ?Icon $icon;
    private ?FolderId $parentFolderId;
    private OwnerId $ownerId;
    private DateTimeValueObject $createdAt;
    private DateTimeValueObject $updatedAt;
    private ?DateTimeValueObject $deletedAt = null;

    public function __construct(
        FolderId $id,
        FolderName $name,
        ?FolderId $parentFolderId,
        OwnerId $ownerId,
        \DateTimeInterface $createdAt,
        ?Icon $icon = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->icon = $icon;
        $this->parentFolderId = $parentFolderId;
        $this->ownerId = $ownerId;
        $this->createdAt = new DateTimeValueObject($createdAt);
        $this->updatedAt = new DateTimeValueObject($createdAt);
    }

    public function rename(FolderName $name, \DateTimeInterface $now): void
    {
        $this->name = $name;
        $this->updatedAt = new DateTimeValueObject($now);
    }

    /**
     * Changes the name and/or the icon (`null` restores the default one); fields that are not given stay as they are.
     *
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
            'name' => new FolderName($value),
            'icon' => Icon::fromNullable($value),
            default => $value,
        };
    }

    public function moveTo(?FolderId $parentFolderId, \DateTimeInterface $now): void
    {
        $this->parentFolderId = $parentFolderId;
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

    public function getId(): FolderId
    {
        return $this->id;
    }

    public function getName(): FolderName
    {
        return $this->name;
    }

    public function getIcon(): ?Icon
    {
        return $this->icon;
    }

    public function getParentFolderId(): ?FolderId
    {
        return $this->parentFolderId;
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
