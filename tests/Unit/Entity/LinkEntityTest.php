<?php

namespace App\Tests\Unit\Entity;

use App\Entity\File;
use App\Entity\Link;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LinkEntityTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidLinkShouldPassValidation(): void
    {
        // Arrange
        $file = new File();
        $file->setFileName('example.txt')
            ->setFilePath('/uploads/example.txt');

        $link = new Link();
        $link->setId(Uuid::v4())
            ->setToken('valid-token')
            ->setFile($file)
            ->setExpiresAt(new DateTimeImmutable('+1 day'));

        // Act
        $violations = $this->validator->validate($link);

        // Assert
        $this->assertCount(
            0,
            $violations,
            'A valid Link entity should not produce validation errors.'
        );
    }

    public function testBlankTokenShouldFailValidation(): void
    {
        // Arrange
        $file = new File();
        $file->setFileName('example.txt')
            ->setFilePath('/uploads/example.txt');

        $link = new Link();
        $link->setId(Uuid::v4())
            ->setToken('')  // Blank, should fail NotBlank
            ->setFile($file)
            ->setExpiresAt(new DateTimeImmutable());

        // Act
        $violations = $this->validator->validate($link);

        // Assert
        $this->assertGreaterThan(
            0,
            $violations->count(),
            'Link with blank token must produce a validation error.'
        );
        $this->assertSame(
            'token',
            $violations[0]->getPropertyPath(),
            'Expected violation on the "token" property.'
        );
    }

    public function testGettersAndSetters(): void
    {
        // Arrange
        $link = new Link();
        $file = new File();

        $uuid     = Uuid::v4();
        $token    = 'test-token';
        $now      = new DateTimeImmutable();
        $password = 'secret';

        // Act
        $link->setId($uuid)
            ->setToken($token)
            ->setFile($file)
            ->setExpiresAt($now)
            ->setPassword($password);

        // Assert
        $this->assertSame($uuid, $link->getId(), 'getId should return the UUID we set.');
        $this->assertSame($token, $link->getToken(), 'getToken should return the token we set.');
        $this->assertSame($file, $link->getFile(), 'getFile should return the File object we set.');
        $this->assertSame($now, $link->getExpiresAt(), 'getExpiresAt should return the DateTime we set.');
        $this->assertSame($password, $link->getPassword(), 'getPassword should return the password we set.');
    }
}
