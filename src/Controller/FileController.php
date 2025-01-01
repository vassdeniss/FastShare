<?php

namespace App\Controller;

use App\Service\FileUploadService;
use Exception;
use Flasher\Prime\FlasherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/file')]
class FileController extends AbstractController
{
    private FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    #[Route('/upload', name: 'app_upload', methods: ['POST'])]
    public function upload(Request $request, FlasherInterface $flasher, LoggerInterface $log): Response
    {
        $uploadedFile  = $request->files->get('file');

        // 1) Check if a file was actually uploaded
        if (!$uploadedFile) {
            $flasher
                ->option('position', 'top-left')
                ->error('No file uploaded.');
            return $this->redirectToRoute('app_root');
        }

        try {
            // 2) Delegate the file upload to our service
            $result = $this->fileUploadService->uploadFile(
                $uploadedFile,
                $this->getParameter('project_root'),
                $this->getParameter('upload_directory')
            );

            // 3) Flash a success message
            $flasher
                ->option('position', 'top-left')
                ->success('File uploaded and saved to the database successfully.');

            // 4) Flash the link
            $flasher
                ->options(['position' => 'top-left', 'timeout' => 60000])
                ->info(sprintf('http://127.0.0.1:8000/link/%s', $result['token']));
        } catch (Exception $ex) {
            $log->error($ex->getMessage());
            $flasher
                ->option('position', 'top-left')
                ->error($ex->getMessage());
        }

        return $this->redirectToRoute('app_root');
    }
}
