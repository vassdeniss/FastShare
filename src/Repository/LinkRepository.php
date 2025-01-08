<?php

namespace App\Repository;

use App\Entity\Link;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class LinkRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $em;
    private FileRepository $fileRepository;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $em, FileRepository $fileRepository)
    {
        parent::__construct($registry, Link::class);
        $this->em = $em;
        $this->fileRepository = $fileRepository;
    }

    /**
     * Creates and saves a new Link for the given File.
     *
     * @param string      $id          The File entity ID.
     * @param string|null $rawPassword The raw password for link protection (optional).
     *
     * @return Link The newly created Link entity.
     */
    public function save(string $id, ?string $rawPassword): Link
    {
        $file = $this->fileRepository->find($id);

        $link = new Link();
        $link->setFile($file);
        $link->setToken(Uuid::v4()->toRfc4122());
        $link->setExpiresAt((new DateTime())->modify('+24 hours'));

        if (!empty($rawPassword)) {
            $hashedPassword = password_hash($rawPassword, PASSWORD_BCRYPT);
            $link->setPassword($hashedPassword);
        }

        $this->em->persist($link);
        $this->em->flush();

        return $link;
    }

    /**
     * Finds a Link entity by its token.
     *
     * @param string $token The link token.
     *
     * @return Link|null The matching Link, or null if none found.
     */
    public function findOneByToken(string $token): ?Link
    {
        return $this->findOneBy(['token' => $token]);
    }

    /**
     * Finds all links that have expired by the given date/time.
     *
     * @param DateTime $now The current date/time reference.
     *
     * @return Link[] An array of expired Link entities.
     */
    public function findExpiredLinks(DateTime $now): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.expiresAt <= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }
}
