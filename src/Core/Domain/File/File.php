<?php

namespace App\Core\Domain\File;

interface File
{
    public function getExtension(string $filename) : string;

    public function generateFilename(
        string $fileExtension,
        string $originalName
    ) : string;
}