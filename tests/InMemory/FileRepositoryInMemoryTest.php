<?php

namespace App\Tests\InMemory;

use App\Entity\File;
use App\Repository\FileRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FileRepositoryInMemoryTest extends KernelTestCase
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
        /** @var FileRepository $fileRepository */
        $fileRepository = $this->entityManager->getRepository(File::class);

        // Act: create & save a File
        $file = $fileRepository->save(
            'test-file.txt',
            '/uploads/test-file.txt',
            123456,
            new DateTime(),
            'image/png'
        );

        // Assert: can be fetched back from the DB
        $savedFile = $this->entityManager->getRepository(File::class)
            ->find($file);

        $this->assertNotNull($savedFile);
        $this->assertSame('test-file.txt', $savedFile->getFileName());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
