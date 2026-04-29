<?php

declare(strict_types=1);

namespace App\Utility {
    function move_uploaded_file(string $source, string $destination): bool
    {
        return \Utility\UploadTest::mockMoveUploadedFile($source, $destination);
    }
}

namespace Utility {

use App\Utility\Upload;
use App\Utility\UploadMoveFailedException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class UploadTest extends TestCase
{
    public static bool $mockUploadShouldSucceed = true;

    public static bool $mockUploadShouldUseLinkAndUnlink = true;

    /** @var array<int, array{source:string,destination:string}> */
    public static array $mockUploadCalls = [];

    private string $initialWorkingDirectory;

    private string $sandboxDirectory;

    private string $storageDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $currentDirectory = getcwd();
        $this->assertIsString($currentDirectory);
        $this->initialWorkingDirectory = $currentDirectory;

        $this->sandboxDirectory = sys_get_temp_dir() . '/upload-test-' . uniqid('', true);
        $this->storageDirectory = $this->sandboxDirectory . '/storage';

        $this->assertTrue(mkdir($this->storageDirectory, 0777, true));
        $this->assertTrue(chdir($this->sandboxDirectory));

        self::$mockUploadShouldSucceed = true;
        self::$mockUploadShouldUseLinkAndUnlink = true;
        self::$mockUploadCalls = [];
    }

    protected function tearDown(): void
    {
        chdir($this->initialWorkingDirectory);
        $this->removeDirectoryRecursively($this->sandboxDirectory);

        parent::tearDown();
    }

    public function testUploadFileShouldThrowForInvalidPayload(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid upload payload');

        Upload::uploadFile(['name' => 'photo.jpeg'], 42);
    }

    public function testUploadFileShouldThrowForDisallowedExtension(): void
    {
        $source = $this->createSourceFile('content');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('This file extension is not allowed. Please upload a JPEG or PNG file');

        Upload::uploadFile([
            'name' => 'photo.gif',
            'size' => 100,
            'tmp_name' => $source,
        ], 42);
    }

    public function testUploadFileShouldThrowWhenFileIsTooLarge(): void
    {
        $source = $this->createSourceFile('content');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File exceeds maximum size (4MB)');

        Upload::uploadFile([
            'name' => 'photo.jpeg',
            'size' => 4000001,
            'tmp_name' => $source,
        ], 42);
    }

    public function testUploadFileShouldThrowWhenMoveUploadedFileFails(): void
    {
        self::$mockUploadShouldSucceed = false;

        $source = $this->createSourceFile('content');

        $this->expectException(UploadMoveFailedException::class);
        $this->expectExceptionMessage('An error occurred. Please contact the administrator.');

        Upload::uploadFile([
            'name' => 'photo.jpeg',
            'size' => 100,
            'tmp_name' => $source,
        ], 42);

        $this->assertSame(1, count(self::$mockUploadCalls));
    }

    public function testUploadFileShouldReturnPictureNameAndMoveFile(): void
    {
        $source = $this->createSourceFile('image-bytes');

        $pictureName = Upload::uploadFile([
            'name' => 'photo.jpeg',
            'size' => 100,
            'tmp_name' => $source,
        ], 42);

        $expectedDestination = $this->storageDirectory . '/42.jpeg';

        $this->assertSame('42.jpeg', $pictureName);
        $this->assertSame(1, count(self::$mockUploadCalls));
        $this->assertSame($source, self::$mockUploadCalls[0]['source']);
        $this->assertSame($expectedDestination, self::$mockUploadCalls[0]['destination']);
        $this->assertFileExists($expectedDestination);
        $this->assertFileDoesNotExist($source);
        $this->assertSame('image-bytes', (string) file_get_contents($expectedDestination));
    }

    public function testUploadFileCanUseMockWithoutFilesystemMove(): void
    {
        self::$mockUploadShouldUseLinkAndUnlink = false;

        $source = $this->createSourceFile('content');

        $pictureName = Upload::uploadFile([
            'name' => 'photo.jpg',
            'size' => 100,
            'tmp_name' => $source,
        ], 77);

        $this->assertSame('77.jpg', $pictureName);
        $this->assertSame(1, count(self::$mockUploadCalls));
        $this->assertFileExists($source);
        $this->assertFileDoesNotExist($this->storageDirectory . '/77.jpg');
    }

    public static function mockMoveUploadedFile(string $source, string $destination): bool
    {
        self::$mockUploadCalls[] = [
            'source' => $source,
            'destination' => $destination,
        ];

        if (!self::$mockUploadShouldSucceed) {
            return false;
        }

        if (!self::$mockUploadShouldUseLinkAndUnlink) {
            return true;
        }

        $destinationDirectory = dirname($destination);
        if (!is_dir($destinationDirectory) && !mkdir($destinationDirectory, 0777, true) && !is_dir($destinationDirectory)) {
            return false;
        }

        if (!is_file($source)) {
            return false;
        }

        if (!link($source, $destination)) {
            return false;
        }

        return unlink($source);
    }

    private function createSourceFile(string $content): string
    {
        $source = tempnam($this->sandboxDirectory, 'upload-src-');
        $this->assertIsString($source);
        $this->assertNotFalse(file_put_contents($source, $content));

        return $source;
    }

    private function removeDirectoryRecursively(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectoryRecursively($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
}
