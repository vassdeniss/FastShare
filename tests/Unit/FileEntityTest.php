<?php

namespace App\Tests\Unit;

use App\Entity\File;
use Monolog\Test\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FileEntityTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }


    public function testValidFileEntityPassesValidation(): void
    {
        // Arrange
        $file = new File();
        $file->setId(Uuid::v4())
            ->setFileName('example.txt')
            ->setFilePath('/uploads/example.txt')
            ->setFileSize(123456)
            ->setMimeType('image/png')
            ->setUploadDate(new \DateTime())
            ->setDownloadCount(0);

        // Act
        $violations = $this->validator->validate($file);

        // Assert
        $this->assertCount(0, $violations, 'Expected 0 violations for a valid File entity');
    }

    public function testBlankFileNameGeneratesViolation(): void
    {
        // Arrange
        $file = new File();
        $file->setId(Uuid::v4())
            ->setFileName('') // blank, should trigger NotBlank violation
            ->setFilePath('/uploads/blank.txt')
            ->setFileSize(123)
            ->setMimeType('image/png')
            ->setUploadDate(new \DateTime());

        // Act
        $violations = $this->validator->validate($file);

        // Assert
        $this->assertGreaterThan(0, count($violations), 'Expected violation for blank fileName.');
        $this->assertSame('fileName', $violations[0]->getPropertyPath());
        $this->assertStringContainsString('cannot be blank', $violations[0]->getMessage());
    }

    public function testTooLongFileNameGeneratesViolation(): void
    {
        // Arrange
        $longName = str_repeat('a', 256); // 256 characters
        $file = new File();
        $file->setId(Uuid::v4())
            ->setFileName($longName)
            ->setFilePath('/uploads/long-name.txt')
            ->setFileSize(123)
            ->setMimeType('image/png')
            ->setUploadDate(new \DateTime());

        // Act
        $violations = $this->validator->validate($file);

        // Assert
        $this->assertGreaterThan(0, count($violations), 'Expected violation for fileName exceeding 255 chars.');
        $this->assertSame('fileName', $violations[0]->getPropertyPath());
        $this->assertStringContainsString('must not exceed 255 characters', $violations[0]->getMessage());
    }

    public function testBlankFilePathGeneratesViolation(): void
    {
        // Arrange
        $file = new File();
        $file->setId(Uuid::v4())
            ->setFileName('text.png')
            ->setFilePath('') // blank, should trigger NotBlank violation
            ->setFileSize(123)
            ->setMimeType('image/png')
            ->setUploadDate(new \DateTime());

        // Act
        $violations = $this->validator->validate($file);

        // Assert
        $this->assertGreaterThan(0, count($violations), 'Expected violation for blank fileName.');
        $this->assertSame('filePath', $violations[0]->getPropertyPath());
        $this->assertStringContainsString('cannot be blank', $violations[0]->getMessage());
    }

    public function testGetterSetter(): void
    {
        // Arrange
        $file = new File();

        $uuid     = Uuid::v4();
        $fileName = 'example.txt';
        $filePath = '/uploads/example.txt';
        $fileSize = 12345;
        $now      = new \DateTimeImmutable();  // or \DateTime
        $count    = 10;
        $mimeType = 'image/png';

        // Act
        $file->setId($uuid)
            ->setFileName($fileName)
            ->setFilePath($filePath)
            ->setFileSize($fileSize)
            ->setUploadDate($now)
            ->setDownloadCount($count)
            ->setMimeType($mimeType);

        // Assert
        $this->assertSame($uuid, $file->getId(), 'File ID should be the same UUID we set.');
        $this->assertSame($fileName, $file->getFileName(), 'File name should match what was set.');
        $this->assertSame($filePath, $file->getFilePath(), 'File path should match what was set.');
        $this->assertSame($fileSize, $file->getFileSize(), 'File size should match what was set.');
        $this->assertSame($now, $file->getUploadDate(), 'Upload date should match what was set.');
        $this->assertSame($count, $file->getDownloadCount(), 'Download count should match what was set.');
        $this->assertSame($mimeType, $file->getMimeType(), 'MIME type should match what was set.');
    }
}
