<?php

namespace App\Controller;

use App\Repository\LinkRepository;
use Flasher\Prime\FlasherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/link')]
class LinkController extends AbstractController
{
    private LinkRepository $linkRepository;

    public function __construct(LinkRepository $linkRepository) {
        $this->linkRepository = $linkRepository;
    }

    /**
     * Renders the Twig view to display the file.
     *
     * @param string $token The token associated with the file.
     *
     * @return Response The rendered Twig template.
     *
     * @throws NotFoundHttpException If the link or file is not found or invalid.
     */
    #[Route('/{token}', name: 'app_view_file', methods: ['GET'])]
    public function viewFile(string $token, LoggerInterface $logger): Response
    {
        $link = $this->linkRepository->findOneByToken($token);
        if (!$link) {
            throw $this->createNotFoundException('Link not found or expired.');
        }

        $file = $link->getFile();
        if (!$file) {
            throw $this->createNotFoundException('File not found.');
        }

        $filePath = $this->getParameter('project_root') . DIRECTORY_SEPARATOR . $file->getFilePath();
        $logger->emergency($filePath);
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File does not exist.');
        }

        return $this->render('link/view.html.twig', [
            'token' => $token,
            'fileName' => $file->getFileName(),
        ]);
    }

    /**
     * Serves the image file securely.
     *
     * @param string $token The token associated with the file.
     *
     * @return BinaryFileResponse The response streaming the image file.
     *
     * @throws NotFoundHttpException If the link or file is not found.
     */
    #[Route('/serve/{token}', name: 'app_serve_file', methods: ['Post'])]
    public function serveFile(string $token): BinaryFileResponse
    {
        $link = $this->linkRepository->findOneByToken($token);
        if (!$link) {
            throw $this->createNotFoundException('Link not found or expired.');
        }

        $file = $link->getFile();
        if (!$file) {
            throw $this->createNotFoundException('File not found.');
        }

        $filePath = $this->getParameter('project_root') . DIRECTORY_SEPARATOR . $file->getFilePath();
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File does not exist.');
        }

        $response = new BinaryFileResponse($filePath);
        // $response->headers->set('Content-Type', $file->getMimeType());
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $file->getFileName()
        );

        return $response;
    }
}
