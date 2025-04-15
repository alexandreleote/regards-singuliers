<?php
// Ce fichier est utilisé uniquement pour le serveur de développement PHP intégré

// Vérifier si la requête est pour un fichier statique dans le dossier build
if (preg_match('/^\/build\//', $_SERVER['REQUEST_URI'])) {
    $filePath = __DIR__ . $_SERVER['REQUEST_URI'];
    
    // Vérifier si le fichier existe
    if (file_exists($filePath)) {
        // Déterminer le type MIME en fonction de l'extension du fichier
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        switch ($extension) {
            case 'css':
                header('Content-Type: text/css');
                break;
            case 'js':
                header('Content-Type: application/javascript');
                break;
            case 'jpg':
            case 'jpeg':
                header('Content-Type: image/jpeg');
                break;
            case 'png':
                header('Content-Type: image/png');
                break;
            case 'webp':
                header('Content-Type: image/webp');
                break;
            case 'svg':
                header('Content-Type: image/svg+xml');
                break;
            case 'woff':
                header('Content-Type: font/woff');
                break;
            case 'woff2':
                header('Content-Type: font/woff2');
                break;
            case 'ttf':
                header('Content-Type: font/ttf');
                break;
            case 'eot':
                header('Content-Type: application/vnd.ms-fontobject');
                break;
            case 'json':
                header('Content-Type: application/json');
                break;
            default:
                header('Content-Type: application/octet-stream');
        }
        
        // Envoyer le fichier
        readfile($filePath);
        exit;
    }
}

// Si ce n'est pas un fichier statique ou si le fichier n'existe pas, continuer avec l'application Symfony
require_once __DIR__ . '/index.php';
