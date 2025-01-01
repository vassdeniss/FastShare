<?php

namespace App\Repository;

use App\Entity\File;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for managing File entities.
 *
 * This repository provides custom methods for interacting with the File entity.
 */
class FileRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $em;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $em)
    {
        parent::__construct($registry, File::class);
        $this->em = $em;
    }

    /**
     * Saves a new File entity to the database.
     * @param string $fileName The name of the file being saved.
     * @param string $filePath The file's storage path.
     * @param int $fileSize The size of the file in bytes.
     * @param DateTimeInterface $uploadDate The date and time when the file was uploaded.
     * @param string $mimeType The MIME of the uploaded file.
     * @return File The newly created File entity.
     */
    public function save(string $fileName, string $filePath, int $fileSize,
                         DateTimeInterface $uploadDate, string $mimeType): File
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

        return $fileEntity;
    }
}
