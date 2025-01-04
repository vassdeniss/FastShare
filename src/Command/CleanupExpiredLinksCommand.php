<?php

namespace App\Command;

use App\Entity\File;
use App\Entity\Link;
use App\Repository\LinkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:cleanup-expired-links',
    description: 'Deletes expired files from the OS and database.',
)]
class CleanupExpiredLinksCommand extends Command
{
    protected static $defaultName = 'app:cleanup-expired-links';
    private LinkRepository $linkRepository;
    private EntityManagerInterface $entityManager;
    private Filesystem $filesystem;

    public function __construct(
        LinkRepository $linkRepository,
        EntityManagerInterface $entityManager,
        Filesystem $filesystem
    ) {
        parent::__construct();
        $this->linkRepository = $linkRepository;
        $this->entityManager = $entityManager;
        $this->filesystem = $filesystem;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Deletes expired files from the OS and database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTime();

        $expiredLinks = $this->linkRepository->findExpiredLinks($now);
        foreach ($expiredLinks as $link) {
            $file = $link->getFile();

            if ($this->filesystem->exists($file->getFilePath())) {
                $this->filesystem->remove($file->getFilePath());
                $io->success("Deleted file: " . $file->getFilePath());
            }

            $this->entityManager->remove($file);
            $this->entityManager->remove($link);
        }

        $this->entityManager->flush();
        $io->success('Expired links and their files have been cleaned up.');
        return Command::SUCCESS;
    }
}
