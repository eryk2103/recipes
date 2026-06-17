<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\File;

class FileUploadService
{
    public function upload(File $file): string
    {
        $fileName = uniqid() . '.' . $file->guessExtension();
        $file->move('uploads/recipe_photos', $fileName);

        return $fileName;
    }
}
