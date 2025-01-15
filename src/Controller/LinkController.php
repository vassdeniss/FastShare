<?php

namespace App\Controller;

use App\Dto\LinkDto;
use App\Service\FileService;
use App\Service\LinkService;
use Flasher\Prime\FlasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/link')]
class LinkController extends AbstractController
{
    private LinkService $linkFileService;
    private FileService $fileService;

    public function __construct(LinkService $linkFileService, FileService $fileService)
    {
        $this->linkFileService = $linkFileService;
        $this->fileService = $fileService;
    }

    /**
     * Displays a file referenced by a link token, optionally requesting a password.
     *
     * @param string           $token   The link token.
     * @param SessionInterface $session The user session.
     *
     * @return Response The rendered view or password form.
     */
    #[Route('/{token}', name: 'app_view_file', methods: ['GET'])]
    public function viewFile(
        string $token,
        SessionInterface $session
    ): Response {
        // 1) Fetch Link
        $link = $this->linkFileService->getLinkByToken($token);

        // 2) If link has no password, show the file immediately
        if (!$link->getPassword()) {
            return $this->renderFileView($link);
        }

        // 3) If link has a password, check if user already validated
        $validatedLinks = $session->get('validated_links', []);
        if (in_array($token, $validatedLinks, true)) {
            return $this->renderFileView($link);
        }

        return $this->render('link/password_form.html.twig', [
            'token' => $token,
        ]);
    }

    /**
     * Validates a user-entered password for a protected link.
     *
     * @param string           $token   The link token.
     * @param Request          $request The current request.
     * @param SessionInterface $session The user session.
     * @param FlasherInterface $flasher The flash message service.
     *
     * @return RedirectResponse Redirects to the file view route on success or error.
     */
    #[Route('/check-password/{token}', name: 'app_check_password', methods: ['POST'])]
    public function checkPassword(
        string $token,
        Request $request,
        SessionInterface $session,
        FlasherInterface $flasher
    ): RedirectResponse {
        // 1) Get the link
        $link = $this->linkFileService->getLinkByToken($token);

        // 2) If no password set, just redirect to the main route
        if (!$link->getPassword()) {
            return $this->redirectToRoute('app_view_file', ['token' => $token]);
        }

        // 3) Compare user input with hashed password
        $enteredPassword = $request->request->get('password', '');
        if (password_verify($enteredPassword, $link->getPassword())) {
            $validatedLinks = $session->get('validated_links', []);
            if (!in_array($token, $validatedLinks, true)) {
                $validatedLinks[] = $token;
            }

            $session->set('validated_links', $validatedLinks);
            return $this->redirectToRoute('app_view_file', ['token' => $token]);
        }

        // 4) Invalid password => show an error message, redirect again
        $flasher
            ->option('position', 'top-left')
            ->error('Invalid password!');
        return $this->redirectToRoute('app_view_file', ['token' => $token]);
    }

    /**
     * Serves the file as a downloadable or inline response.
     *
     * @param Request $request The current request (to check query params).
     * @param string  $token   The link token referencing the file.
     *
     * @return BinaryFileResponse The streamed file response.
     */
    #[Route('/serve/{token}', name: 'app_serve_file', methods: ['GET', 'POST'])]
    public function serveFile(Request $request, string $token): BinaryFileResponse
    {
        // 1) Retrieve Link and File
        $link     = $this->linkFileService->getLinkByToken($token);
        $file     = $this->linkFileService->getFileFromLink($link);
        $filePath = $this->linkFileService->getAbsoluteFilePath(
            $file->getFilePath(),
            $this->getParameter('project_root')
        );

        // 2) update download count
        $isDownloaded = $request->query->getBoolean('isDownloaded');
        if ($isDownloaded) {
            $file->setDownloadCount($file->getDownloadCount() + 1);
            $this->fileService->updateFile($file->getId(), downloadCount: $file->getDownloadCount());
        }

        // 3) Build the BinaryFileResponse
        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', $file->getMimeType());
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $file->getFileName()
        );

        return $response;
    }

    /**
     * Renders the file view page.
     *
     * @param LinkDto $link The Link DTO.
     *
     * @return Response A rendered twig template containing file details.
     */
    private function renderFileView(LinkDto $link): Response
    {
        $file = $this->linkFileService->getFileFromLink($link);
        $filePath = $this->linkFileService->getAbsoluteFilePath(
            $file->getFilePath(),
            $this->getParameter('project_root'));

        $parameters = [
            'token'    => $link->getToken(),
            'mime'     => $file->getMimeType(),
            'path'     => $filePath,
            'fileName' => $file->getFileName(),
            'downloads' => $file->getDownloadCount()
        ];

        if ($this->linkFileService->isZipFile($file->getFileName())) {
            $parameters['zipContents'] = $this->linkFileService->getZipContents($filePath);
        }

        return $this->render('link/view.html.twig', $parameters);
    }
}
