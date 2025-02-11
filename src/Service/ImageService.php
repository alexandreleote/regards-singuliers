<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageService
{
    private $params;
    private $storageType;
    private $localUploadDirectory;
    private $cloudStorageConfig;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
        $this->storageType = $params->get('app.image_storage_type');
        $this->localUploadDirectory = $params->get('app.local_upload_directory');
        $this->cloudStorageConfig = $params->get('app.cloud_storage_config');
    }

    public function uploadImage(UploadedFile $file, string $directory = ''): string
    {
        switch ($this->storageType) {
            case 'local':
                return $this->uploadLocalImage($file, $directory);
            case 'cloud':
                return $this->uploadCloudImage($file, $directory);
            default:
                throw new \Exception('Invalid storage type configured');
        }
    }

    private function uploadLocalImage(UploadedFile $file, string $directory = ''): string
    {
        $uploadDir = $this->localUploadDirectory . '/' . $directory;
        
        // Ensure directory exists
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = uniqid() . '.' . $file->guessExtension();
        $file->move($uploadDir, $fileName);

        return $fileName;
    }

    private function uploadCloudImage(UploadedFile $file, string $directory = ''): string
    {
        // Placeholder for cloud storage logic
        // You would implement specific cloud provider logic here (AWS S3, Google Cloud Storage, etc.)
        // Example with AWS S3:
        /*
        $s3Client = new S3Client([
            'version' => 'latest',
            'region'  => $this->cloudStorageConfig['region'],
            'credentials' => [
                'key'    => $this->cloudStorageConfig['key'],
                'secret' => $this->cloudStorageConfig['secret'],
            ],
        ]);

        $result = $s3Client->putObject([
            'Bucket' => $this->cloudStorageConfig['bucket'],
            'Key'    => $directory . '/' . $fileName,
            'Body'   => fopen($file->getRealPath(), 'r'),
            'ACL'    => 'public-read',
        ]);

        return $result->get('ObjectURL');
        */
        throw new \Exception('Cloud storage not yet implemented');
    }

    public function getImageUrl(string $fileName, string $directory = ''): string
    {
        switch ($this->storageType) {
            case 'local':
                return $this->getLocalImageUrl($fileName, $directory);
            case 'cloud':
                return $this->getCloudImageUrl($fileName, $directory);
            default:
                throw new \Exception('Invalid storage type configured');
        }
    }

    private function getLocalImageUrl(string $fileName, string $directory = ''): string
    {
        return '/img/' . ($directory ? $directory . '/' : '') . $fileName;
    }

    private function getCloudImageUrl(string $fileName, string $directory = ''): string
    {
        // Placeholder for cloud storage URL generation
        return $this->cloudStorageConfig['base_url'] . '/' . $directory . '/' . $fileName;
    }
}
