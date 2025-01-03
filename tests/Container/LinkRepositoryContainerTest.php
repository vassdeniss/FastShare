<?php

namespace App\Tests\Container;

use App\Entity\Link;
use App\Repository\LinkRepository;
use App\Tests\Creator;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Testcontainers\Container\MySQLContainer;
use Testcontainers\Wait\WaitForLog;

class LinkRepositoryContainerTest extends KernelTestCase
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
        /** @var LinkRepository $linkRepository */
        $linkRepository = $this->entityManager->getRepository(Link::class);

        $file = Creator::createFile("example.txt");
        $this->entityManager->persist($file);
        $this->entityManager->flush();

        // Act: create & save a Link
        $link = $linkRepository->save($file);

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
        $link = $linkRepository->save($file);

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
