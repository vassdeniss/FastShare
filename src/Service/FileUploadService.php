<?php

namespace App\Service;

use App\Repository\FileRepository;
use App\Repository\LinkRepository;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploadService
{
    public const ALLOWED_MIME_TYPES = [
        // images
        'image/jpeg',
        'image/png',
        'image/gif',

        // videos
        'video/mp4',
        'video/mpeg',
        'video/quicktime',

        // audio
        'audio/mpeg',
        'audio/wav',
        'audio/ogg',

        // other
        'application/zip',
        'application/x-zip-compressed',
        'application/octet-stream',
    ];

    public const MAX_FILE_SIZE = 1.5e+9; // 1.5 GB in bytes

    private FileRepository $fileRepository;
    private LinkRepository $linkRepository;
    private SluggerInterface $slugger;

    public function __construct(
        FileRepository $fileRepository,
        LinkRepository $linkRepository,
        SluggerInterface $slugger
    ) {
        $this->fileRepository = $fileRepository;
        $this->linkRepository = $linkRepository;
        $this->slugger = $slugger;
    }

    /**
     * Uploads the file, validates it, moves it to the target directory,
     * saves it to the database, and creates the associated link.
     * @throws Exception if the file is invalid or the upload fails.
     * @return array ['token' => string, 'fileName' => string]
     */
    public function uploadFile(
        UploadedFile $file,
        string $projectRoot,
        string $uploadDirectory,
        ?string $rawPassword = null
    ): array {
        // 1) Validate the file
        $this->validateFile($file);

        // 2) Generate a safe unique filename
        $newFileName = $this->generateFileName($file);

        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();

        // 3) Move the file to the uploads directory
        $targetPath = $projectRoot
            . DIRECTORY_SEPARATOR
            . $uploadDirectory
            . DIRECTORY_SEPARATOR;
        $file->move($targetPath, $newFileName);

        // 4) Persist file to the database
        $fileEntity = $this->fileRepository->save(
            $newFileName,
            $uploadDirectory . DIRECTORY_SEPARATOR . $newFileName,
            $fileSize,
            new DateTime(),
            $mimeType
        );

        // 5) Generate a link and save
        $linkEntity = $this->linkRepository->save($fileEntity, $rawPassword);

        return [
            'token'    => $linkEntity->getToken(),
            'fileName' => $newFileName,
        ];
    }

    /**
     * Validate file size and MIME type.
     * @throws Exception if the file is invalid.
     */
    private function validateFile(UploadedFile $file): void
    {
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            throw new Exception('Invalid file type.');
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new Exception('File is too large. Maximum size allowed is 1.5 GB.');
        }
    }

    /**
     * Generate a slug-safe file name with a unique suffix.
     */
    private function generateFileName(UploadedFile $file): string
    {
        $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeBaseName     = $this->slugger->slug($originalFileName)->toString();
        $extension        = $file->guessExtension();

        return sprintf('%s-%s.%s', $safeBaseName, uniqid(), $extension);
    }
}
