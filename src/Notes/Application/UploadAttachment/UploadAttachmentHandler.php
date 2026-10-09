<?php

declare(strict_types=1);

namespace App\Notes\Application\UploadAttachment;

use App\Core\Domain\File\File;
use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Domain\Exception\AttachmentEmpty;
use App\Notes\Domain\Exception\AttachmentTooLarge;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Exception\NoteNotFound;
use App\Notes\Domain\Model\Attachment;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\Service\FileStoragePort;
use App\Notes\Domain\ValueObject\AttachmentId;
use App\Notes\Domain\ValueObject\FileName;
use App\Notes\Domain\ValueObject\FileSizeBytes;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\MimeType;
use App\Notes\Domain\ValueObject\NoteId;
use App\Notes\Domain\ValueObject\OwnerId;
use App\Notes\Domain\ValueObject\StorageKey;

class UploadAttachmentHandler implements TransactionalCommandHandler
{
    public function __construct(
        private AttachmentRepository $attachmentRepository,
        private FolderRepository $folderRepository,
        private NoteRepository $noteRepository,
        private FileStoragePort $fileStorage,
        private File $file,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(UploadAttachmentCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);

        if ($command->sizeBytes <= 0) {
            throw new AttachmentEmpty();
        }
        if ($command->sizeBytes > FileSizeBytes::MAX_ATTACHMENT_BYTES) {
            throw new AttachmentTooLarge(FileSizeBytes::MAX_ATTACHMENT_BYTES);
        }

        $folderId = null;
        $noteId = null;

        if ($command->noteId !== null) {
            $note = $this->noteRepository->getById($command->noteId);
            if ($note === null || !$note->belongsTo($ownerId) || $note->isDeleted()) {
                throw new NoteNotFound($command->noteId);
            }
            $noteId = new NoteId($command->noteId);
        } elseif ($command->folderId !== null) {
            $folder = $this->folderRepository->getById($command->folderId);
            if ($folder === null || !$folder->belongsTo($ownerId) || $folder->isDeleted()) {
                throw new FolderNotFound($command->folderId);
            }
            $folderId = new FolderId($command->folderId);
        }

        $extension = $this->file->getExtension($command->originalFileName);
        $storageKey = new StorageKey($command->id . ($extension !== '' ? '.' . $extension : ''));

        $this->fileStorage->store($command->tmpFilePath, $storageKey->value());

        $attachment = new Attachment(
            id: new AttachmentId($command->id),
            folderId: $folderId,
            noteId: $noteId,
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
