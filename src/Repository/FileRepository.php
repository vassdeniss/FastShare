<?php

namespace App\Repository;

use App\Entity\File;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class FileRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $em;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $em)
    {
        parent::__construct($registry, File::class);

        $this->em = $em;
    }

    /**
     * Creates and saves a File entity.
     *
     * @param string             $fileName   The name of the file
     * @param string             $filePath   The path to the file
     * @param int                $fileSize   The size of the file in bytes
     * @param DateTimeInterface  $uploadDate The date the file was uploaded
     */
    public function save(string $fileName, string $filePath, int $fileSize,
                         DateTimeInterface $uploadDate): void
    {
        $fileEntity = new File();
        $fileEntity
            ->setFileName($fileName)
            ->setFilePath($filePath)
            ->setFileSize($fileSize)
            ->setUploadDate($uploadDate);

        $this->em->persist($fileEntity);
        $this->em->flush();
    }

    //    /**
    //     * @return File[] Returns an array of File objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?File
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
