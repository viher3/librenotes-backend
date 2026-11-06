<?php

declare(strict_types=1);

namespace App\Notes\Domain\Exception;

/**
 * The folder chosen as a destination does not exist (or is not the user's, or is in the trash). Same code as
 * {@see FolderNotFound}, but a different HTTP status: the request is wrong, not a missing resource.
 */
class DestinationFolderNotFound extends FolderNotFound
{
}
