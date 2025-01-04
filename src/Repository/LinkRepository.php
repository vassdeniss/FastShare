<?php

namespace App\Repository;

use App\Entity\File;
use App\Entity\Link;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * Repository for managing Link entities.
 *
 * This repository provides custom methods for interacting with the Link entity.
 */
class LinkRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $em;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $em)
    {
        parent::__construct($registry, Link::class);
        $this->em = $em;
    }

    /**
     * Creates a new Link entity for the given File and persists it to the database.
     * @param File $file The File entity for which the link is being generated.
     * @return Link The newly created Link entity.
     * @throws \DateMalformedStringException
     */
    public function save(File $file): Link
    {
        $link = new Link();
        $link->setFile($file);
        $link->setToken(Uuid::v4()->toRfc4122());
        $link->setExpiresAt((new \DateTime())
             ->modify('+24 hours'));

        $this->em->persist($link);
        $this->em->flush();

        return $link;
    }

    /**
     * Retrieves a single Link entity that matches the provided token.
     * @param string $token The token associated with the Link.
     * @return Link|null The Link entity if found, or null if no matching Link is found.
     */
    public function findOneByToken(string $token): ?Link
    {
        return $this->findOneBy(['token' => $token]);
    }

    /**
     * Finds all expired links based on the current date and time.
     * @param DateTime $now The current date and time used to filter expired links.
     * @return Link[] Returns an array of expired `Link` entities.
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
