<?php

namespace App\Tests\InMemory\Service;

use App\Entity\File;
use App\Entity\Link;
use App\Repository\FileRepository;
use App\Repository\LinkRepository;
use App\Service\FileService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;

class FileServiceInMemoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager = null;
    private FileService $fileService;

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

        /** @var FileRepository $fileRepository */
        $fileRepository = $this->entityManager->getRepository(File::class);
        /** @var LinkRepository $linkRepository */
        $linkRepository = $this->entityManager->getRepository(Link::class);
        $slugger = new AsciiSlugger();

        $this->fileService = new FileService($fileRepository, $linkRepository, $slugger);
    }

    public function testUploadFile(): void
    {
        // Arrange
        $projectRoot = sys_get_temp_dir();
        $uploadDirectory = 'uploads_test';

        // Create a temporary UploadedFile for testing
        $sourceFile = tempnam(sys_get_temp_dir(), 'testfile');
        file_put_contents($sourceFile, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAUA' .
            'AAAFCAYAAACNbyblAAAAHElEQVQI12P4' .
            '//8/w38GIAXDIBKE0DHxgljNBAAO9TXL' .
            '0Y4OHwAAAABJRU5ErkJggg==')); // create a dummy file
        $uploadedFile = new UploadedFile(
            $sourceFile,
            'test.png',
            'image/png',
            null,
            true // set test mode true so that move() works on non-existent files
        );

        // Act
        $result = $this->fileService->uploadFile($uploadedFile, $projectRoot, $uploadDirectory);

        // Assert
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('fileName', $result);
        $this->assertStringStartsWith('test-', $result['fileName']);
        $this->assertNotEmpty($result['token']);

        // Cleanup
        @unlink($sourceFile);
    }

    public function testUploadFileInvalidMimeTypeThrowsException(): void
    {
        // Arrange
        $projectRoot = sys_get_temp_dir();
        $uploadDirectory = 'uploads_test';

        // Create a temporary UploadedFile for testing
        $sourceFile = tempnam(sys_get_temp_dir(), 'testfile');
        file_put_contents($sourceFile, 'hello world'); // create a dummy file
        $uploadedFile = new UploadedFile(
            $sourceFile,
            'test.txt',
            'text/plain',
            null,
            true // set test mode true so that move() works on non-existent files
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid file type.');

        // Act
        $this->fileService->uploadFile($uploadedFile, $projectRoot, $uploadDirectory);

        // Assert
        // Already arranged
    }

    public function testUploadFileTooLargeThrowsException(): void
    {
        // Arrange
        $projectRoot = sys_get_temp_dir();
        $uploadDirectory = 'uploads_test';

        // Create a temporary UploadedFile for testing
        $sourceFile = tempnam(sys_get_temp_dir(), 'testfile');

        $fileSize = 1.5e+10;
        $handle = fopen($sourceFile, 'wb');
        if ($handle === false) {
            die('Failed to create a file');
        }

        fseek($handle, $fileSize - 1);
        fwrite($handle, "\0");
        fclose($handle);

        $uploadedFile = new UploadedFile(
            $sourceFile,
            'test.png',
            'image/png',
            null,
            true // set test mode true so that move() works on non-existent files
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('File is too large. Maximum size allowed is 1.5 GB.');

        // Act
        $this->fileService->uploadFile($uploadedFile, $projectRoot, $uploadDirectory);

        // Assert
        // Already arranged
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
