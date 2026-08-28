<?php

declare(strict_types=1);

namespace App\Notes\Domain\Model;

use App\Core\Domain\Aggregate\AggregateRoot;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Notes\Domain\ValueObject\AttachmentId;
use App\Notes\Domain\ValueObject\FileName;
use App\Notes\Domain\ValueObject\FileSizeBytes;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\MimeType;
use App\Notes\Domain\ValueObject\OwnerId;
use App\Notes\Domain\ValueObject\StorageKey;

class Attachment extends AggregateRoot
{
    private AttachmentId $id;
    private FolderId $folderId;
    private OwnerId $ownerId;
    private FileName $fileName;
    private MimeType $mimeType;
    private FileSizeBytes $sizeBytes;
    private StorageKey $storageKey;
    private DateTimeValueObject $createdAt;

    public function __construct(
        AttachmentId $id,
        FolderId $folderId,
        OwnerId $ownerId,
        FileName $fileName,
        MimeType $mimeType,
        FileSizeBytes $sizeBytes,
        StorageKey $storageKey,
        \DateTimeInterface $createdAt
    ) {
        $this->id = $id;
        $this->folderId = $folderId;
        $this->ownerId = $ownerId;
        $this->fileName = $fileName;
        $this->mimeType = $mimeType;
        $this->sizeBytes = $sizeBytes;
        $this->storageKey = $storageKey;
        $this->createdAt = new DateTimeValueObject($createdAt);
    }

    public function belongsTo(OwnerId $ownerId): bool
    {
        return $this->ownerId->equals($ownerId);
    }

    public function getId(): AttachmentId
    {
        return $this->id;
    }

    public function getFolderId(): FolderId
    {
        return $this->folderId;
    }

    public function getOwnerId(): OwnerId
    {
        return $this->ownerId;
    }

    public function getFileName(): FileName
    {
        return $this->fileName;
    }

    public function getMimeType(): MimeType
    {
        return $this->mimeType;
    }

    public function getSizeBytes(): FileSizeBytes
    {
        return $this->sizeBytes;
    }

    public function getStorageKey(): StorageKey
    {
        return $this->storageKey;
    }

    public function getCreatedAt(): DateTimeValueObject
    {
        return $this->createdAt;
    }
}
