<?php

namespace App\Service;

use App\Entity\File;
use App\Entity\Link;
use App\Repository\LinkRepository;
use HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use ZipArchive;

class LinkFileService
{
    private LinkRepository $linkRepository;

    public function __construct(LinkRepository $linkRepository)
    {
        $this->linkRepository = $linkRepository;
    }

    /**
     * Given a token, returns a validated Link entity.
     * @throws NotFoundHttpException if the token is invalid or expired.
     */
    public function getLinkByToken(string $token): Link
    {
        $link = $this->linkRepository->findOneByToken($token);
        if (!$link) {
            throw new NotFoundHttpException('Link not found or expired.');
        }

        return $link;
    }

    /**
     * Given a Link entity, returns the associated File entity.
     * @throws NotFoundHttpException if the file is missing.
     */
    public function getFileFromLink(Link $link): File
    {
        $file = $link->getFile();
        if (!$file) {
            throw new NotFoundHttpException('File not found.');
        }

        return $file;
    }

    /**
     * Given a File entity and project root, returns the absolute file path.
     * @throws NotFoundHttpException if the file is not present on disk.
     */
    public function getAbsoluteFilePath(File $file, string $projectRoot): string
    {
        $filePath = $projectRoot . DIRECTORY_SEPARATOR . $file->getFilePath();

        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('File does not exist.');
        }

        return $filePath;
    }

    /**
     * Determines if a file name belongs to a ZIP archive.
     */
    public function isZipFile(string $fileName): bool
    {
        return strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) === 'zip';
    }

    /**
     * Returns an array of the file names inside a ZIP archive.
     * @throws HttpException if we fail to open the ZIP.
     */
    public function getZipContents(string $zipPath): array
    {
        $zipContents = [];
        $zip = new ZipArchive();

        if ($zip->open($zipPath) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $zipContents[] = $zip->getNameIndex($i);
            }
            $zip->close();
        } else {
            throw new HttpException(500, 'Unable to open ZIP file.');
        }

        return $zipContents;
    }
}
