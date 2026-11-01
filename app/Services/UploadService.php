<?php
declare(strict_types=1);

namespace App\Services;

class UploadService
{
    private const ALLOWED = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private const MAX_BYTES = 2 * 1024 * 1024;

    /**
     * Save an uploaded image into public/assets/uploads. Returns [publicPath|null, error|null].
     */
    public static function image(array $file, string $prefix = 'img'): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [null, 'The upload failed. Please try again.'];
        }
        if ($file['size'] > self::MAX_BYTES) {
            return [null, 'The image is larger than 2 MB.'];
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::ALLOWED[$mime]) || @getimagesize($file['tmp_name']) === false) {
            return [null, 'Upload a JPG, PNG or WebP image.'];
        }
        $name = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . self::ALLOWED[$mime];
        $dir = BASE_PATH . '/public/assets/uploads';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
            return [null, 'Could not save the image. Check folder permissions.'];
        }
        return ['assets/uploads/' . $name, null];
    }
}
