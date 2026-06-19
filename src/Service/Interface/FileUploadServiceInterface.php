<?php

namespace App\Service\Interface;

use Symfony\Component\HttpFoundation\File\File;

interface FileUploadServiceInterface
{
    function upload(File $file);
}
