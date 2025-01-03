<?php

namespace App\Tests\Unit;

use App\Entity\File;
use App\Entity\Link;
use App\Repository\FileRepository;
use App\Repository\LinkRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class LinkRepositoryTest extends TestCase
{
    public function testSave(): void
    {
        // Arrange: Set up mocks and dependencies
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);
        $linkRepository = new LinkRepository($managerRegistry, $entityManager);
        $file = new File();

        // Set up expectations for the mocked EntityManager
        // 1. The persist method should be called once with a File entity.
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($entity) {
                return $entity instanceof Link; // Assert that a Link entity is being persisted.
            }));

        // 2. The flush method should be called once to save the changes.
        $entityManager->expects($this->once())
            ->method('flush');

        // Act: Call the method under test
        $linkRepository->save($file);

        // Assert: (Implicit in mock expectations)
        // Verify that the persist and flush methods were called as expected.
    }

    public function testFindOneByToken(): void
    {
        // Arrange: Set up mocks and dependencies
        $token = 'test-token';
        $expectedLink = new Link();
        $expectedLink->setToken($token);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);
        $linkRepository = $this->getMockBuilder(LinkRepository::class)
            ->setConstructorArgs([$managerRegistry, $entityManager])
            ->onlyMethods(['findOneBy'])
            ->getMock();
        $linkRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => $token])
            ->willReturn($expectedLink);

        // Act: Call the method under test
        $result = $linkRepository->findOneByToken($token);

        // Assert: Verify the returned result matches the expected result
        $this->assertSame($expectedLink, $result);
    }
}
