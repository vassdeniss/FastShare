<?php

namespace App\Service;

use App\Dto\FileDto;
use App\Dto\LinkDto;
use App\Mapper\LinkMapper;
use App\Repository\LinkRepository;
use HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use ZipArchive;

class LinkService
{
    private LinkRepository $linkRepository;

    public function __construct(LinkRepository $linkRepository)
    {
        $this->linkRepository = $linkRepository;
    }

    /**
     * Returns a validated Link DTO by token.
     *
     * @param string $token The link token.
     *
     * @throws NotFoundHttpException When the token is invalid or expired.
     *
     * @return LinkDto The corresponding Link DTO.
     */
    public function getLinkByToken(string $token): LinkDto
    {
        $link = $this->linkRepository->findOneByToken($token);
        if (!$link) {
            throw new NotFoundHttpException('Link not found or expired.');
        }

        return LinkMapper::entityToDto($link);
    }

    /**
     * Retrieves the associated File DTO from a Link DTO.
     *
     * @param LinkDto $link The Link DTO.
     *
     * @throws NotFoundHttpException When the linked file is missing.
     *
     * @return FileDto The associated File DTO.
     */
    public function getFileFromLink(LinkDto $link): FileDto
    {
        $file = $link->getFile();
        if (!$file) {
            throw new NotFoundHttpException('File not found.');
        }

        return $file;
    }

    /**
     * Builds the absolute file path for a given file path and project root.
     *
     * @param string $filePath    The relative file path.
     * @param string $projectRoot The project root directory.
     *
     * @throws NotFoundHttpException When the file does not exist on disk.
     *
     * @return string The absolute file path.
     */
    public function getAbsoluteFilePath(string $filePath, string $projectRoot): string
    {
        $filePath = $projectRoot . DIRECTORY_SEPARATOR . $filePath;

        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('File does not exist.');
        }

        return $filePath;
    }

    /**
     * Checks if a file name represents a ZIP archive.
     *
     * @param string $fileName The file name to check.
     *
     * @return bool True if the file is a ZIP, false otherwise.
     */
    public function isZipFile(string $fileName): bool
    {
        return strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) === 'zip';
    }

    /**
     * Retrieves the list of files inside a ZIP archive.
     *
     * @param string $zipPath The ZIP file path.
     *
     * @throws HttpException When the ZIP file cannot be opened.
     *
     * @return array An array of file names in the archive.
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
            return $zipContents;
        }

        throw new HttpException(500, 'Unable to open ZIP file.');
    }
}
