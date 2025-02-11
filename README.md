# Regards Singuliers Project

## Image Storage Configuration

This project supports two types of image storage:

### Local Storage
- Default storage type
- Images are stored in `public/img/`
- Configuration: `APP_IMAGE_STORAGE_TYPE=local`

### Cloud Storage
To switch to cloud storage:

1. Update `.env` file:
```env
APP_IMAGE_STORAGE_TYPE=cloud
APP_CLOUD_STORAGE_PROVIDER=aws  # or other providers
APP_CLOUD_STORAGE_BUCKET=your-bucket-name
APP_CLOUD_STORAGE_REGION=eu-west-1
APP_CLOUD_STORAGE_ACCESS_KEY=your_access_key
APP_CLOUD_STORAGE_SECRET_KEY=your_secret_key
APP_CLOUD_STORAGE_BASE_URL=https://your-cloud-storage-url.com/bucket
```

2. Implement cloud-specific upload logic in `ImageService`
   - Currently supports placeholder for AWS S3
   - Extend the `uploadCloudImage()` method for your specific cloud provider

### Using the ImageService

```php
// Example usage in a controller
public function uploadImage(Request $request, ImageService $imageService)
{
    $uploadedFile = $request->files->get('image');
    $fileName = $imageService->uploadImage($uploadedFile, 'services');
    
    // Get URL for displaying the image
    $imageUrl = $imageService->getImageUrl($fileName, 'services');
}
```

### Switching Between Storage Types
- Change `APP_IMAGE_STORAGE_TYPE` in `.env`
- No code changes required in most cases
- Implement provider-specific logic in `ImageService`

## Security Notes
- Never commit sensitive cloud credentials to version control
- Use environment variables for configuration
- Implement proper access controls and permissions
