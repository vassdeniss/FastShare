<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class RootController extends AbstractController
{
    #[Route('/', name: 'app_root')]
    public function index(): Response
    {
        return $this->render('root/index.html.twig');
    }

    #[Route('/upload', name: 'app_upload')]
    public function upload(Request $request, SluggerInterface $slugger): Response
    {
        $file = $request->files->get('file');

        if (!$file) {
            return new Response('No file uploaded.', 400);
        }

        $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFileName = $slugger->slug($originalFileName);
        $newFileName = $safeFileName.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->getParameter('upload_directory'), $newFileName);
        } catch (FileException $e) {
            return new Response('Failed to upload file.', 500);
        }

        return new Response("File uploaded successfully.");
    }
}
