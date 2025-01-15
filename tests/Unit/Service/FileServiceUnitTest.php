<?php

namespace App\Tests\Unit\Service;

use App\Dto\FileDto;
use App\Entity\Link;
use App\Repository\FileRepository;
use App\Repository\LinkRepository;
use App\Service\FileService;
use App\Tests\Creator;
use DateTime;
use DateTimeInterface;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Uid\Uuid;

class FileServiceUnitTest extends TestCase
{
    private FileService $service;
    private FileRepository $fileRepo;
    private LinkRepository $linkRepo;
    private SluggerInterface $slugger;

    protected function setUp(): void
    {
        // Create mocks for dependencies
        $this->fileRepo = $this->createMock(FileRepository::class);
        $this->linkRepo = $this->createMock(LinkRepository::class);
        $this->slugger = $this->createMock(SluggerInterface::class);

        // Instantiate FileService with mocked dependencies
        $this->service = new FileService($this->fileRepo, $this->linkRepo, $this->slugger);
    }

    public function testUploadFile(): void
    {
        // Arrange
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->expects($this->any())->method('getMimeType')->willReturn('image/jpeg');
        $uploadedFile->expects($this->any())->method('getSize')->willReturn(1024);
        $uploadedFile->expects($this->any())->method('getClientOriginalName')->willReturn('test.jpg');

        // Expect move() to be called once with specific parameters
        $uploadedFile->expects($this->once())->method('move')
            ->with(
                $this->stringContains(DIRECTORY_SEPARATOR . 'uploadDir' . DIRECTORY_SEPARATOR),
                $this->callback(function ($filename) {
                    return str_starts_with($filename, 'safefilename-');
                })
            );

        // Setup slugger to return a safe basename
        $this->slugger->expects($this->any())->method('slug')->willReturn(new UnicodeString("safefilename"));

        // Prepare fake UUID to return from FileRepository->save()
        $fakeUuid = Uuid::v4();
        $this->fileRepo->expects($this->once())
            ->method('save')
            ->with(
                $this->anything(), // newFileName
                $this->callback(function ($path) {
                    // Check that the file path contains the upload directory and filename
                    return str_contains($path, 'uploadDir');
                }),
                1024, // file size
                $this->isInstanceOf(DateTime::class),
                'image/jpeg'
            )
            ->willReturn($fakeUuid);

        // Create a fake Link entity with a token to return from LinkRepository->save()
        $fakeLink = new Link();
        $fakeLink->setToken('fake-token');

        $this->linkRepo->expects($this->once())
            ->method('save')
            ->with($fakeUuid, null)
            ->willReturn($fakeLink);

        // Act
        $result = $this->service->uploadFile($uploadedFile, '/projectRoot', 'uploadDir');

        // Assert
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('fileName', $result);
        $this->assertEquals('fake-token', $result['token']);
        $this->assertStringStartsWith('safefilename-', $result['fileName']);
    }

    public function testUploadFileInvalidMimeTypeThrowsException(): void
    {
        // Arrange
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('getMimeType')->willReturn('in/valid');
        $uploadedFile->method('getSize')->willReturn(1024);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid file type.');

        // Act
        $this->service->uploadFile($uploadedFile, '/projectRoot', 'uploadDir');

        // Assert
        // Already arranged
    }

    public function testUploadFileTooLargeThrowsException(): void
    {
        // Arrange
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('getMimeType')->willReturn('image/jpeg');
        $uploadedFile->method('getSize')->willReturn((int)(FileService::MAX_FILE_SIZE + 1));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('File is too large. Maximum size allowed is 1.5 GB.');

        // Act
        $this->service->uploadFile($uploadedFile, '/projectRoot', 'uploadDir');

        // Assert
        // Already arranged
    }

    public function testUpdateFile(): void
    {
        // Arrange
        $dummyFile = Creator::createFile('updatedName.jpg');

        // Expect repository->edit() to be called with specific parameters
        $this->fileRepo->expects($this->once())
            ->method('edit')
            ->with(
                $this->equalTo('some-id'),
                $this->equalTo('newName'),
                $this->equalTo('newPath'),
                $this->equalTo(4096),
                $this->isInstanceOf(DateTimeInterface::class),
                $this->equalTo('image/png'),
                $this->equalTo(10)
            )
            ->willReturn($dummyFile);

        // Act
        $result = $this->service->updateFile(
            'some-id',
            'newName',
            'newPath',
            4096,
            new DateTime(),
            'image/png',
            10
        );

        // Assert
        $this->assertInstanceOf(FileDto::class, $result);
    }
}
