<?php

namespace App\Controller;

use App\Repository\FileRepository;
use DateTime;
use Exception;
use Flasher\Prime\FlasherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class RootController extends AbstractController
{
    private FileRepository $fileRepository;

    public function __construct(FileRepository $fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }

    #[Route('/', name: 'app_root', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('root/index.html.twig');
    }

    #[Route('/upload', name: 'app_upload', methods: ['POST'])]
    public function upload(Request $request, SluggerInterface $slugger, FlasherInterface $flasher, LoggerInterface $log): Response
    {
        $file = $request->files->get('file');

        if (!$file) {
            $flasher
                ->option('position', 'top-left')
                ->error('No file uploaded.');
            return $this->redirectToRoute('app_root');
        }

        // Magic number: 1.5gb into bytes
        $maxFileSize = 1.5e+9;
        $fileSize = $file->getSize();
        $log->info($maxFileSize);
        $log->info($file->getSize());

        if ($fileSize > $maxFileSize) {
            $flasher
                ->option('position', 'top-left')
                ->error('File is too large. Maximum size allowed is 1.5 GB.');
            return $this->redirectToRoute('app_root');
        }

        $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFileName = $slugger->slug($originalFileName);
        $newFileName = $safeFileName.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->getParameter('upload_directory'), $newFileName);

            $this->fileRepository->save(
                $newFileName,
                $this->getParameter('upload_directory_folder_only') . '/' . $newFileName,
                $fileSize,
                new DateTime()
            );

            $flasher
                ->option('position', 'top-left')
                ->success('File uploaded and saved to the database successfully.');

            return $this->redirectToRoute('app_root');
        } catch (Exception $e) {
            $log->error($e->getMessage());
            $flasher
                ->option('position', 'top-left')
                ->error('Failed to upload file.');
            return $this->redirectToRoute('app_root');
        }
    }
}
