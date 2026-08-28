<?php

namespace App\Core\Domain\File;

class DomainFile implements File
{
    public function getExtension(string $filename) : string
    {
        $file_info = pathinfo($filename);
        return $file_info['extension'];
    }

    public function generateFilename(
        string $fileExtension,
        string $originalName
    ) : string
    {
        return md5(str_replace('.' . $fileExtension, '', $originalName)) . '.' . $fileExtension;
    }
}