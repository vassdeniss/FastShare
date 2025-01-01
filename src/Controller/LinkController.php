<?php

namespace App\Controller;

use App\Service\LinkFileService;
use HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/link')]
class LinkController extends AbstractController
{
    private LinkFileService $linkFileService;

    public function __construct(LinkFileService $linkFileService)
    {
        $this->linkFileService = $linkFileService;
    }

    /**
     * Renders the Twig view to display the file.
     * @param string $token The token associated with the file.
     * @return Response The rendered Twig template.
     * @throws HttpException If the zip file cannot be opened.
     */
    #[Route('/{token}', name: 'app_view_file', methods: ['GET'])]
    public function viewFile(string $token): Response
    {
        // 1) Retrieve Link and File
        $link     = $this->linkFileService->getLinkByToken($token);
        $file     = $this->linkFileService->getFileFromLink($link);
        $filePath = $this->linkFileService->getAbsoluteFilePath(
            $file,
            $this->getParameter('project_root')
        );

        // 2) If it's a ZIP, get contents
        $parameters = [
            'token'    => $token,
            'mime'     => $file->getMimeType(),
            'path'     => $filePath,
            'fileName' => $file->getFileName(),
        ];

        if ($this->linkFileService->isZipFile($file->getFileName())) {
            $parameters['zipContents'] = $this->linkFileService->getZipContents($filePath);
        }

        // 3) Render the template
        return $this->render('link/view.html.twig', $parameters);
    }

    /**
     * Serves the image file securely.
     * @param string $token The token associated with the file.
     * @return BinaryFileResponse The response streaming the image file.
     */
    #[Route('/serve/{token}', name: 'app_serve_file', methods: ['GET', 'POST'])]
    public function serveFile(string $token): BinaryFileResponse
    {
        // 1) Retrieve Link and File
        $link     = $this->linkFileService->getLinkByToken($token);
        $file     = $this->linkFileService->getFileFromLink($link);
        $filePath = $this->linkFileService->getAbsoluteFilePath(
            $file,
            $this->getParameter('project_root')
        );

        // 2) Build the BinaryFileResponse
        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', $file->getMimeType());
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $file->getFileName()
        );

        return $response;
    }
}
