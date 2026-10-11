<?php

declare(strict_types=1);

namespace App\Notes\Domain\ValueObject;

enum SearchResultType: string
{
    case Note = 'note';
    case Folder = 'folder';
    case Attachment = 'attachment';
}
