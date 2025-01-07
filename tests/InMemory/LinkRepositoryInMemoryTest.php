<?php

namespace App\Tests\InMemory;

use App\Entity\Link;
use App\Repository\LinkRepository;
use App\Tests\Creator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LinkRepositoryInMemoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager = null;

    protected function setup(): void
    {
        // Boot the Symfony kernel in 'test' environment
        self::bootKernel();

        // Grab the EntityManager from the service container
        $this->entityManager = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        // Create schema in memory
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);
    }

    public function testSave(): void
    {
        // Arrange: retrieve the repository from the container
        /** @var LinkRepository $linkRepository */
        $linkRepository = $this->entityManager->getRepository(Link::class);

        $file = Creator::createFile("example.txt");
        $this->entityManager->persist($file);
        $this->entityManager->flush();

        // Act: create & save a Link
        $link = $linkRepository->save($file, null);

        // Assert: can be fetched back from the DB
        $savedLink = $linkRepository->find($link->getId());

        $this->assertNotNull($savedLink);
        $this->assertSame($savedLink->getId(), $link->getId());
    }

    public function testFindOneByToken(): void
    {
        // Arrange: retrieve the repository from the container
        /** @var LinkRepository $linkRepository */
        $linkRepository = $this->entityManager->getRepository(Link::class);

        $file = Creator::createFile("example.txt");
        $this->entityManager->persist($file);
        $this->entityManager->flush();

        // Act: create & save a Link
        $link = $linkRepository->save($file, null);

        // Assert: can be fetched back from the DB
        $savedLink = $linkRepository->find($link->getId());

        $this->assertNotNull($savedLink);
        $this->assertSame($savedLink->getId(), $link->getId());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
