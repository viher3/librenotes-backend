<?php

declare(strict_types=1);

namespace App\Notes\Domain\ValueObject;

/** The kinds of things a user can select in the tree and act on in bulk. */
enum ItemType: string
{
    case Folder = 'folder';
    case Note = 'note';
    case Link = 'link';
    case Attachment = 'attachment';
}
