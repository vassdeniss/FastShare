<?php

namespace App\Tests\Repository;

use App\Entity\File;
use App\Repository\FileRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the FileRepository class.
 *
 * This test ensures that the FileRepository interacts correctly with the
 * Doctrine EntityManager and performs database operations as expected.
 */
class FileRepositoryTest extends TestCase
{
    /**
     * Tests the save method of FileRepository.
     *
     * Ensures that the save method correctly:
     * - Creates a File entity.
     * - Calls the EntityManager's persist method with the File entity.
     * - Calls the EntityManager's flush method to save the changes.
     *
     * @return void
     */
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
            new DateTime()
        );

        // Assert: (Implicit in mock expectations)
        // Verify that the persist and flush methods were called as expected.
    }
}
