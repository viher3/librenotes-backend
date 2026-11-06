<?php

declare(strict_types=1);

namespace App\Notes\Domain\Model;

/** The top-most items of a selection, already loaded: whatever sits inside another selected item is not listed. */
readonly class SelectedItems
{
    /**
     * @param Folder[]     $folders
     * @param Note[]       $notes
     * @param Link[]       $links
     * @param Attachment[] $attachments
     */
    public function __construct(
        public array $folders,
        public array $notes,
        public array $links,
        public array $attachments
    ) {
    }

    public function count(): int
    {
        return count($this->folders) + count($this->notes) + count($this->links) + count($this->attachments);
    }
}
