<?php

namespace App\Tests\Service;

use App\Service\FileUploadService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

class FileUploadServiceTest extends TestCase
{
    public function testUploadMovesFileToUploadsDirectoryWithGuessedExtension(): void
    {
        $file = $this->createMock(File::class);
        $file->method('guessExtension')->willReturn('jpg');
        $file->expects($this->once())->method('move')
            ->with('uploads/recipe_photos', $this->callback(fn (string $name) => str_ends_with($name, '.jpg')));

        $filename = new FileUploadService()->upload($file);

        self::assertStringEndsWith('.jpg', $filename);
    }

    public function testUploadProducesAUniqueFilenameForEachCall(): void
    {
        $service = new FileUploadService();

        $file1 = $this->createStub(File::class);
        $file1->method('guessExtension')->willReturn('png');

        $file2 = $this->createStub(File::class);
        $file2->method('guessExtension')->willReturn('png');

        self::assertNotSame($service->upload($file1), $service->upload($file2));
    }
}
