<?php

declare(strict_types=1);

namespace App\Notes\Domain\ValueObject;

use App\Core\Domain\ValueObject\StringValueObject;

class NoteContent extends StringValueObject
{
    /**
     * Content encrypted by the client starts with this marker (`lnenc:v1:<envelope>`). The server never holds the
     * key: it only recognises the marker, to keep the ciphertext out of search results and to flag the note.
     */
    public const string ENCRYPTED_PREFIX = 'lnenc:';

    public function isEncrypted(): bool
    {
        return str_starts_with($this->value(), self::ENCRYPTED_PREFIX);
    }
}
