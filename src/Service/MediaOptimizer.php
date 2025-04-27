<?php

namespace App\Service;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class MediaOptimizer
{
    private $params;
    private $logger;
    private $projectDir;

    public function __construct(ParameterBagInterface $params, LoggerInterface $logger)
    {
        $this->params = $params;
        $this->logger = $logger;
        $this->projectDir = $this->params->get('kernel.project_dir');
    }

    /**
     * Optimise une image et la convertit en WebP
     */
    public function optimizeImage(string $imagePath): array
    {
        $result = [
            'original' => $imagePath,
            'webp' => null,
            'success' => false
        ];
        
        try {
            // Vérifier que le fichier existe
            if (!file_exists($imagePath)) {
                throw new \Exception("Le fichier n'existe pas: $imagePath");
            }

            // Obtenir le chemin relatif par rapport au dossier du projet
            $relativeImagePath = $this->getRelativePath($imagePath);
            
            // Déterminer le chemin de sortie WebP
            $pathInfo = pathinfo($imagePath);
            $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
            
            // Créer le processus de conversion
            $ffmpegPath = $this->getFfmpegPath();
            
            // Utiliser ImageMagick ou cwebp si disponible
            if ($this->isCommandAvailable('cwebp')) {
                $process = new Process([
                    'cwebp',
                    '-q', '80',
                    $imagePath,
                    '-o', $webpPath
                ]);
            } elseif ($this->isCommandAvailable('convert')) {
                $process = new Process([
                    'convert',
                    $imagePath,
                    '-quality', '80',
                    $webpPath
                ]);
            } elseif ($ffmpegPath) {
                // Fallback sur FFmpeg si disponible
                $process = new Process([
                    $ffmpegPath,
                    '-i', $imagePath,
                    '-c:v', 'libwebp',
                    '-quality', '80',
                    '-compression_level', '6',
                    $webpPath
                ]);
            } else {
                throw new \Exception("Aucun outil de conversion d'image disponible");
            }
            
            $process->setTimeout(300);
            $process->run();
            
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
            
            // Supprimer le fichier original si la conversion a réussi
            if (file_exists($webpPath)) {
                unlink($imagePath);
                $result['webp'] = $webpPath;
                $result['success'] = true;
                $this->logger->info("Image convertie en WebP: $webpPath");
            }
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de l'optimisation de l'image: " . $e->getMessage());
            $result['error'] = $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Optimise une vidéo et la convertit en WebM
     */
    public function optimizeVideo(string $videoPath): array
    {
        $result = [
            'original' => $videoPath,
            'webm' => null,
            'success' => false
        ];
        
        try {
            // Vérifier que le fichier existe
            if (!file_exists($videoPath)) {
                throw new \Exception("Le fichier n'existe pas: $videoPath");
            }
            
            // Obtenir le chemin relatif par rapport au dossier du projet
            $relativeVideoPath = $this->getRelativePath($videoPath);
            
            // Déterminer le chemin de sortie WebM
            $pathInfo = pathinfo($videoPath);
            $webmPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webm';
            
            // Obtenir le chemin de FFmpeg
            $ffmpegPath = $this->getFfmpegPath();
            if (!$ffmpegPath) {
                throw new \Exception("FFmpeg n'est pas disponible");
            }
            
            // Convertir en WebM
            $webmProcess = new Process([
                $ffmpegPath,
                '-i', $videoPath,
                '-c:v', 'libvpx-vp9',
                '-crf', '30',
                '-b:v', '0',
                '-deadline', 'good',
                '-cpu-used', '2',
                '-c:a', 'libopus',
                '-b:a', '96k',
                $webmPath
            ]);
            
            $webmProcess->setTimeout(600);
            $webmProcess->run();
            
            if (!$webmProcess->isSuccessful()) {
                throw new ProcessFailedException($webmProcess);
            }
            
            // Supprimer le fichier original si la conversion a réussi
            if (file_exists($webmPath)) {
                unlink($videoPath);
                $result['webm'] = $webmPath;
                $result['success'] = true;
                $this->logger->info("Vidéo convertie en WebM: $webmPath");
            }
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de l'optimisation de la vidéo: " . $e->getMessage());
            $result['error'] = $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Obtient le chemin relatif d'un fichier par rapport au dossier du projet
     */
    private function getRelativePath(string $path): string
    {
        return str_replace($this->projectDir . '/', '', $path);
    }
    
    /**
     * Vérifie si une commande est disponible
     */
    private function isCommandAvailable(string $command): bool
    {
        $whereIsCommand = PHP_OS_FAMILY === 'Windows' ? "where $command" : "which $command";
        
        $process = Process::fromShellCommandline($whereIsCommand);
        $process->run();
        
        return $process->isSuccessful();
    }
    
    /**
     * Obtient le chemin de FFmpeg
     */
    private function getFfmpegPath(): ?string
    {
        // Essayer d'utiliser ffmpeg-static si disponible
        $nodeModulesPath = $this->projectDir . '/node_modules/ffmpeg-static';
        if (file_exists($nodeModulesPath)) {
            $ffmpegPath = PHP_OS_FAMILY === 'Windows' 
                ? $nodeModulesPath . '/ffmpeg.exe'
                : $nodeModulesPath . '/ffmpeg';
                
            if (file_exists($ffmpegPath)) {
                return $ffmpegPath;
            }
        }
        
        // Sinon, vérifier si ffmpeg est disponible dans le PATH
        if ($this->isCommandAvailable('ffmpeg')) {
            return 'ffmpeg';
        }
        
        return null;
    }
}
