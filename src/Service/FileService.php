<?php

namespace App\Service;

use App\Dto\FileDto;
use App\Entity\File;
use App\Mapper\FileMapper;
use App\Repository\FileRepository;
use App\Repository\LinkRepository;
use DateTime;
use DateTimeInterface;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileService
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
     * Uploads, validates, saves the file and creates its link.
     *
     * @param UploadedFile    $file            The file to be uploaded.
     * @param string          $projectRoot     The project root directory.
     * @param string          $uploadDirectory The upload directory within the project.
     * @param string|null     $rawPassword     The raw password for link protection (optional).
     *
     * @throws Exception When the file is invalid or fails to upload.
     *
     * @return array {
     *     @type string 'token'    The generated link token.
     *     @type string 'fileName' The new file name.
     * }
     */
    public function uploadFile(
        UploadedFile $file,
        string $projectRoot,
        string $uploadDirectory,
        ?string $rawPassword = null,
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
        $id = $this->fileRepository->save(
            $newFileName,
            $uploadDirectory . DIRECTORY_SEPARATOR . $newFileName,
            $fileSize,
            new DateTime(),
            $mimeType
        );

        // 5) Generate a link and save
        $linkEntity = $this->linkRepository->save($id, $rawPassword);

        return [
            'token'    => $linkEntity->getToken(),
            'fileName' => $newFileName,
        ];
    }

    /**
     * Updates an existing File entity with new data.
     *
     * @param string                 $id            The File entity ID.
     * @param string|null            $fileName      The new file name (optional).
     * @param string|null            $filePath      The new file path (optional).
     * @param int|null               $fileSize      The new file size (optional).
     * @param DateTimeInterface|null $uploadDate    The upload date (optional).
     * @param string|null            $mimeType      The new mime type (optional).
     * @param int|null               $downloadCount The new download count (optional).
     *
     * @return FileDto The updated File DTO.
     */
    public function updateFile(
        string $id,
        ?string $fileName = null,
        ?string $filePath = null,
        ?int $fileSize = null,
        ?DateTimeInterface $uploadDate = null,
        ?string $mimeType = null,
        ?int $downloadCount = null
    ): FileDto {
        return FileMapper::entityToDto($this->fileRepository->edit(
            $id,
            $fileName,
            $filePath,
            $fileSize,
            $uploadDate,
            $mimeType,
            $downloadCount
        ));
    }

    /**
     * Validates the file size and MIME type.
     *
     * @param UploadedFile $file The uploaded file to validate.
     *
     * @throws Exception When the file is too large or has an invalid type.
     *
     * @return void
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
     * Generates a slug-friendly, unique filename.
     *
     * @param UploadedFile $file The uploaded file.
     *
     * @return string The generated unique filename.
     */
    private function generateFileName(UploadedFile $file): string
    {
        $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeBaseName     = $this->slugger->slug($originalFileName)->toString();
        $extension        = $file->guessExtension();

        return sprintf('%s-%s.%s', $safeBaseName, uniqid(), $extension);
    }
}
