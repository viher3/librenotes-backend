<?php

declare(strict_types=1);

namespace App\Notes\Domain\Model;

use App\Core\Domain\Aggregate\AggregateRoot;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\FolderName;
use App\Notes\Domain\ValueObject\OwnerId;

class Folder extends AggregateRoot
{
    private FolderId $id;
    private FolderName $name;
    private ?FolderId $parentFolderId;
    private OwnerId $ownerId;
    private DateTimeValueObject $createdAt;
    private DateTimeValueObject $updatedAt;

    public function __construct(
        FolderId $id,
        FolderName $name,
        ?FolderId $parentFolderId,
        OwnerId $ownerId,
        \DateTimeInterface $createdAt
    ) {
        $this->id = $id;
        $this->name = $name;
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

    public function moveTo(?FolderId $parentFolderId, \DateTimeInterface $now): void
    {
        $this->parentFolderId = $parentFolderId;
        $this->updatedAt = new DateTimeValueObject($now);
    }

    public function belongsTo(OwnerId $ownerId): bool
    {
        return $this->ownerId->equals($ownerId);
    }

    public function getId(): FolderId
    {
        return $this->id;
    }

    public function getName(): FolderName
    {
        return $this->name;
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
}
