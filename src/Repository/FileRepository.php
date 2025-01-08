<?php

namespace App\Repository;

use App\Entity\File;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class FileRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $em;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $em)
    {
        parent::__construct($registry, File::class);
        $this->em = $em;
    }

    /**
     * Creates and saves a new File entity.
     *
     * @param string            $fileName   The file name.
     * @param string            $filePath   The relative path to the file.
     * @param int               $fileSize   The file size in bytes.
     * @param DateTimeInterface $uploadDate The date/time of upload.
     * @param string            $mimeType   The file's MIME type.
     *
     * @return Uuid The newly created File entity's UUID.
     */
    public function save(string $fileName, string $filePath, int $fileSize,
                         DateTimeInterface $uploadDate, string $mimeType): Uuid
    {
        $fileEntity = new File();
        $fileEntity
            ->setFileName($fileName)
            ->setFilePath($filePath)
            ->setFileSize($fileSize)
            ->setUploadDate($uploadDate)
            ->setMimeType($mimeType);

        $this->em->persist($fileEntity);
        $this->em->flush();

        return $fileEntity->getId();
    }

    /**
     * Updates an existing File entity.
     *
     * @param string                 $id            The File entity ID.
     * @param string|null            $fileName      The new file name (optional).
     * @param string|null            $filePath      The new file path (optional).
     * @param int|null               $fileSize      The new file size (optional).
     * @param DateTimeInterface|null $uploadDate    The new upload date (optional).
     * @param string|null            $mimeType      The new MIME type (optional).
     * @param int|null               $downloadCount The new download count (optional).
     *
     * @return File The updated File entity.
     */
    public function edit(
        string $id,
        ?string $fileName = null,
        ?string $filePath = null,
        ?int $fileSize = null,
        ?DateTimeInterface $uploadDate = null,
        ?string $mimeType = null,
        ?int $downloadCount = null,
    ): File
    {
        $file = $this->find($id);

        if ($fileName !== null) {
            $file->setFileName($fileName);
        }
        if ($filePath !== null) {
            $file->setFilePath($filePath);
        }
        if ($fileSize !== null) {
            $file->setFileSize($fileSize);
        }
        if ($uploadDate !== null) {
            $file->setUploadDate($uploadDate);
        }
        if ($mimeType !== null) {
            $file->setMimeType($mimeType);
        }
        if ($downloadCount !== null) {
            $file->setDownloadCount($downloadCount);
        }

        $this->em->flush();

        return $file;
    }
}
