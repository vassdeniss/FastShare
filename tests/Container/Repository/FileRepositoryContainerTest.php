<?php

namespace App\Tests\Container\Repository;

use App\Entity\File;
use App\Repository\FileRepository;
use DateTime;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Testcontainers\Container\MySQLContainer;
use Testcontainers\Wait\WaitForLog;

class FileRepositoryContainerTest extends KernelTestCase
{
    private static ?MySQLContainer $mysqlContainer = null;
    private static ?string $dsn = null;

    public static function setUpBeforeClass(): void
    {
        // Spin up MySQL container only once for this class
        self::$mysqlContainer = MySQLContainer::make('8.0')
            ->withMySQLDatabase('test_db')
            ->withMySQLUser('test_user', 'test_pass')
            ->withWait(new WaitForLog('ready for connections'))
            ->run();

        // Build the DSN
        self::$dsn = sprintf(
            'mysql://test_user:test_pass@%s/test_db',
            self::$mysqlContainer->getAddress()
        );

        // Force the environment or parameter so Doctrine uses this DSN
        putenv('DATABASE_URL=' . self::$dsn);
    }

    public static function tearDownAfterClass(): void
    {
        self::$mysqlContainer?->stop();
    }

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
