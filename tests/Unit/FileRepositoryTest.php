<?php

namespace App\Tests\Unit;

use App\Entity\File;
use App\Repository\FileRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class FileRepositoryTest extends TestCase
{
    public function testSave(): void
    {
        // Arrange: Set up mocks and dependencies
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);
        $fileRepository = new FileRepository($managerRegistry, $entityManager);

        // Set up expectations for the mocked EntityManager
        // 1. The persist method should be called once with a File entity.
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($entity) {
                return $entity instanceof File; // Assert that a File entity is being persisted.
            }));

        // 2. The flush method should be called once to save the changes.
        $entityManager->expects($this->once())
            ->method('flush');

        // Act: Call the method under test
        $fileRepository->save(
            'test-file.txt',
            '/uploads/test-file.txt',
            123456,
            new DateTime(),
            "image/png"
        );

        // Assert: (Implicit in mock expectations)
        // Verify that the persist and flush methods were called as expected.
    }
}
