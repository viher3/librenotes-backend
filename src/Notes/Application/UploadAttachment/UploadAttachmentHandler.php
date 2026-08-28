<?php

declare(strict_types=1);

namespace App\Notes\Application\UploadAttachment;

use App\Core\Domain\File\File;
use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Model\Attachment;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Service\FileStoragePort;
use App\Notes\Domain\ValueObject\AttachmentId;
use App\Notes\Domain\ValueObject\FileName;
use App\Notes\Domain\ValueObject\FileSizeBytes;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\MimeType;
use App\Notes\Domain\ValueObject\OwnerId;
use App\Notes\Domain\ValueObject\StorageKey;

class UploadAttachmentHandler implements TransactionalCommandHandler
{
    public function __construct(
        private AttachmentRepository $attachmentRepository,
        private FolderRepository $folderRepository,
        private FileStoragePort $fileStorage,
        private File $file,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(UploadAttachmentCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $folderId = new FolderId($command->folderId);
        $folder = $this->folderRepository->getById($folderId->value());

        if ($folder === null || !$folder->belongsTo($ownerId)) {
            throw new FolderNotFound($command->folderId);
        }

        $extension = $this->file->getExtension($command->originalFileName);
        $storageKey = new StorageKey($command->id . ($extension !== '' ? '.' . $extension : ''));

        $this->fileStorage->store($command->tmpFilePath, $storageKey->value());

        $attachment = new Attachment(
            id: new AttachmentId($command->id),
            folderId: $folderId,
            ownerId: $ownerId,
            fileName: new FileName($command->originalFileName),
            mimeType: new MimeType($command->mimeType),
            sizeBytes: new FileSizeBytes($command->sizeBytes),
            storageKey: $storageKey,
            createdAt: $this->dateTimeProvider->now()
        );

        $this->attachmentRepository->save($attachment);
    }
}
