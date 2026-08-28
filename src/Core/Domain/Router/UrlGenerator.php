<?php

namespace App\Core\Domain\Router;

interface UrlGenerator
{
    public function generate() : string;

    public function generateUploadBaseurl() : string;
}