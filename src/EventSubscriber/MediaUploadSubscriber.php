<?php

namespace App\EventSubscriber;

use App\Service\MediaOptimizer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class MediaUploadSubscriber implements EventSubscriberInterface
{
    private $mediaOptimizer;
    private $logger;
    private $uploadedFiles = [];

    public function __construct(MediaOptimizer $mediaOptimizer, LoggerInterface $logger)
    {
        $this->mediaOptimizer = $mediaOptimizer;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Traiter les médias après la réponse pour ne pas ralentir l'expérience utilisateur
            KernelEvents::TERMINATE => 'processUploadedMedia',
        ];
    }

    /**
     * Enregistre un fichier média pour traitement ultérieur
     */
    public function registerMediaForProcessing(string $filePath): void
    {
        $this->uploadedFiles[] = $filePath;
    }

    /**
     * Traite les médias uploadés après l'envoi de la réponse HTTP
     */
    public function processUploadedMedia(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        
        // Détecter les uploads de fichiers via le formulaire
        $this->detectUploadsFromRequest($request);
        
        // Traiter tous les fichiers enregistrés
        foreach ($this->uploadedFiles as $filePath) {
            $this->processMedia($filePath);
        }
        
        // Réinitialiser la liste
        $this->uploadedFiles = [];
    }
    
    /**
     * Détecte les uploads de fichiers dans la requête
     */
    private function detectUploadsFromRequest(Request $request): void
    {
        // Vérifier s'il s'agit d'une requête POST avec des fichiers
        if (!$request->isMethod('POST') || empty($request->files->all())) {
            return;
        }
        
        // Parcourir tous les fichiers uploadés
        foreach ($request->files->all() as $filesBag) {
            if (is_array($filesBag)) {
                foreach ($filesBag as $file) {
                    if ($file && $file->isValid()) {
                        $this->uploadedFiles[] = $file->getRealPath();
                    }
                }
            } elseif ($filesBag && $filesBag->isValid()) {
                $this->uploadedFiles[] = $filesBag->getRealPath();
            }
        }
    }
    
    /**
     * Traite un fichier média selon son type
     */
    private function processMedia(string $filePath): void
    {
        if (!file_exists($filePath)) {
            $this->logger->warning("Fichier non trouvé pour optimisation: $filePath");
            return;
        }
        
        $mimeType = mime_content_type($filePath);
        
        // Traiter selon le type MIME
        if (strpos($mimeType, 'image/') === 0) {
            // Exclure les images déjà en WebP
            if ($mimeType !== 'image/webp') {
                $this->mediaOptimizer->optimizeImage($filePath);
            }
        } elseif (strpos($mimeType, 'video/') === 0) {
            // Exclure les vidéos déjà en WebM
            if ($mimeType !== 'video/webm') {
                $this->mediaOptimizer->optimizeVideo($filePath);
            }
        }
    }
}
