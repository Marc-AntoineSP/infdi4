<?php

namespace App\Utility;

use InvalidArgumentException;
use UnexpectedValueException;

class Upload
{

    /**
     * @param array<string, mixed> $file
     * @param int|string $fileName
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     * @throws UploadMoveFailedException
     */
    public static function uploadFile(array $file, $fileName): string
    {
        $currentDirectory = getcwd();
        $uploadDirectory = "/storage/";

        $fileExtensionsAllowed = ['jpeg', 'jpg', 'png'];

        if (!isset($file['name'], $file['size'], $file['tmp_name'])) {
            throw new InvalidArgumentException('Invalid upload payload');
        }

        $fileSize = $file['size'];
        $fileTmpName = $file['tmp_name'];

        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $pictureName = basename($fileName . '.'. $fileExtension);

        $uploadPath = $currentDirectory . $uploadDirectory . $pictureName;

        if (!in_array($fileExtension, $fileExtensionsAllowed)) {
            throw new UnexpectedValueException("This file extension is not allowed. Please upload a JPEG or PNG file");
        }

        if ($fileSize > 4000000) {
            throw new InvalidArgumentException("File exceeds maximum size (4MB)");
        }

        $didUpload = move_uploaded_file($fileTmpName, $uploadPath);

        if ($didUpload) {
            return $pictureName;
        }

        throw new UploadMoveFailedException("An error occurred. Please contact the administrator.");
    }
}
