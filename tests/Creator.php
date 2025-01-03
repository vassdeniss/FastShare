<?php

namespace App\Tests;

use App\Entity\File;
use App\Entity\Link;
use Symfony\Component\Uid\Uuid;

class Creator
{
    /**
     * Creates a new File entity with default values, allowing a single unique attribute to be provided.
     * @param string|null $fileName
     * @return File
     */
    public static function createFile(string $fileName = null): File
    {
        $file = new File();
        $file->setId(Uuid::v4())
            ->setFileName($fileName ?? 'default_file.txt')
            ->setFilePath('/default/path/' . ($fileName ?? 'default_file.txt'))
            ->setFileSize(1024)
            ->setMimeType('application/octet-stream')
            ->setUploadDate(new \DateTimeImmutable())
            ->setDownloadCount(0);

        return $file;
    }

    /**
     * Creates a new Link entity with default values, allowing a single unique attribute to be provided.
     * @param File|null $file
     * @param string|null $token
     * @return Link
     */
    public static function createLink(File $file = null, string $token = null): Link
    {
        $link = new Link();
        $link->setId(Uuid::v4())
            ->setToken($token ?? bin2hex(random_bytes(8)))
            ->setFile($file ?? self::createFile())
            ->setExpiresAt((new \DateTime())->modify('+7 days'));

        return $link;
    }
}
