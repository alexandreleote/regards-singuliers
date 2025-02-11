<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{
    #[Route('/images/{filename}', name: 'app_image')]
    public function serveImage(string $filename): Response
    {
        $imagePath = $this->getParameter('kernel.project_dir') . '/public/img/' . $filename;
        
        if (!file_exists($imagePath)) {
            throw $this->createNotFoundException('The image does not exist');
        }

        return new BinaryFileResponse($imagePath);
    }
}
