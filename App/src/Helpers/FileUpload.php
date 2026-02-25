<?php
namespace App\Helpers;

class FileUpload
{
    private static array $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx'
    ];

    private static array $maxSizes = [
        'image' => 5 * 1024 * 1024, // 5MB for images
        'document' => 10 * 1024 * 1024, // 10MB for documents
        'default' => 2 * 1024 * 1024 // 2MB default
    ];

    /**
     * Upload a file with validation and resource limits
     */
    public static function upload(array $file, string $uploadDir = 'uploads/', array $options = []): array
    {
        // Check storage limits if tenant context exists
        if (isset($options['check_limits']) && $options['check_limits']) {
            $limitMiddleware = new \App\Middleware\ResourceLimitMiddleware();
            if (!$limitMiddleware->checkStorageLimit($file['size'])) {
                return [
                    'success' => false,
                    'error' => 'Storage limit exceeded. Please upgrade your subscription plan.'
                ];
            }
        }

        // Validate file
        $validation = self::validateFile($file, $options);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => $validation['error']
            ];
        }

        // Generate unique filename
        $filename = self::generateUniqueFilename($file['name'], $options['prefix'] ?? '');

        // Create upload directory if it doesn't exist
        $fullUploadDir = self::getUploadPath($uploadDir);
        if (!is_dir($fullUploadDir)) {
            mkdir($fullUploadDir, 0755, true);
        }

        // Move uploaded file
        $filepath = $fullUploadDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $uploadDir . $filename,
                'full_path' => $filepath,
                'size' => $file['size'],
                'type' => $file['type']
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to move uploaded file'
        ];
    }

    /**
     * Upload multiple files
     */
    public static function uploadMultiple(array $files, string $uploadDir = 'uploads/', array $options = []): array
    {
        $results = [];

        foreach ($files as $key => $file) {
            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $result = self::upload($file, $uploadDir, $options);
            $results[$key] = $result;
        }

        return $results;
    }

    /**
     * Validate uploaded file
     */
    public static function validateFile(array $file, array $options = []): array
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'valid' => false,
                'error' => self::getUploadErrorMessage($file['error'])
            ];
        }

        // Check file type
        $allowedTypes = $options['allowed_types'] ?? self::$allowedTypes;
        if (!isset($allowedTypes[$file['type']])) {
            return [
                'valid' => false,
                'error' => 'File type not allowed. Allowed types: ' . implode(', ', array_keys($allowedTypes))
            ];
        }

        // Check file size
        $maxSize = self::getMaxSize($file['type'], $options);
        if ($file['size'] > $maxSize) {
            return [
                'valid' => false,
                'error' => 'File size too large. Maximum size: ' . self::formatBytes($maxSize)
            ];
        }

        // Additional validation for images
        if (strpos($file['type'], 'image/') === 0) {
            $imageInfo = getimagesize($file['tmp_name']);
            if (!$imageInfo) {
                return [
                    'valid' => false,
                    'error' => 'Invalid image file'
                ];
            }

            // Check image dimensions if specified
            if (isset($options['max_width']) && $imageInfo[0] > $options['max_width']) {
                return [
                    'valid' => false,
                    'error' => "Image width too large. Maximum width: {$options['max_width']}px"
                ];
            }

            if (isset($options['max_height']) && $imageInfo[1] > $options['max_height']) {
                return [
                    'valid' => false,
                    'error' => "Image height too large. Maximum height: {$options['max_height']}px"
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Delete uploaded file
     */
    public static function delete(string $filepath): bool
    {
        $fullPath = self::getUploadPath($filepath);
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    /**
     * Get file info
     */
    public static function getFileInfo(string $filepath): ?array
    {
        $fullPath = self::getUploadPath($filepath);
        if (!file_exists($fullPath)) {
            return null;
        }

        $size = filesize($fullPath);
        $mimeType = mime_content_type($fullPath);
        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);

        return [
            'path' => $filepath,
            'full_path' => $fullPath,
            'size' => $size,
            'formatted_size' => self::formatBytes($size),
            'mime_type' => $mimeType,
            'extension' => $extension,
            'modified' => filemtime($fullPath)
        ];
    }

    /**
     * Generate secure download URL
     */
    public static function getDownloadUrl(string $filepath, int $expires = 3600): string
    {
        $timestamp = time() + $expires;
        $token = hash_hmac('sha256', $filepath . $timestamp, $_ENV['APP_SECRET'] ?? 'default-secret');

        return "/download?file=" . urlencode($filepath) . "&expires={$timestamp}&token={$token}";
    }

    /**
     * Validate download token
     */
    public static function validateDownloadToken(string $filepath, int $expires, string $token): bool
    {
        $expectedToken = hash_hmac('sha256', $filepath . $expires, $_ENV['APP_SECRET'] ?? 'default-secret');

        return hash_equals($expectedToken, $token) && $expires > time();
    }

    /**
     * Clean up old files
     */
    public static function cleanupOldFiles(string $directory, int $maxAge = 86400): int
    {
        $fullPath = self::getUploadPath($directory);
        if (!is_dir($fullPath)) {
            return 0;
        }

        $files = glob($fullPath . '*');
        $deleted = 0;

        foreach ($files as $file) {
            if (is_file($file) && (time() - filemtime($file)) > $maxAge) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Get upload path
     */
    private static function getUploadPath(string $path): string
    {
        // If path starts with uploads/, treat it as relative to uploads directory
        if (strpos($path, 'uploads/') === 0) {
            return __DIR__ . '/../../public/' . $path;
        }

        // Otherwise, treat as absolute path relative to public directory
        return __DIR__ . '/../../public/uploads/' . ltrim($path, '/');
    }

    /**
     * Generate unique filename
     */
    private static function generateUniqueFilename(string $originalName, string $prefix = ''): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $basename = pathinfo($originalName, PATHINFO_FILENAME);

        // Sanitize filename
        $basename = preg_replace('/[^a-zA-Z0-9-_]/', '_', $basename);

        // Generate unique name
        $uniqueId = uniqid($prefix, true);
        return $prefix . $uniqueId . '_' . substr($basename, 0, 50) . '.' . $extension;
    }

    /**
     * Get maximum file size based on type
     */
    private static function getMaxSize(string $mimeType, array $options): int
    {
        if (isset($options['max_size'])) {
            return $options['max_size'];
        }

        if (strpos($mimeType, 'image/') === 0) {
            return self::$maxSizes['image'];
        }

        if (in_array($mimeType, ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
            return self::$maxSizes['document'];
        }

        return self::$maxSizes['default'];
    }

    /**
     * Get upload error message
     */
    private static function getUploadErrorMessage(int $error): string
    {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File size exceeds server limit';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File size exceeds form limit';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }

    /**
     * Format bytes to human readable format
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Create thumbnail for image
     */
    public static function createThumbnail(string $filepath, int $width = 200, int $height = 200): ?string
    {
        $fullPath = self::getUploadPath($filepath);

        if (!file_exists($fullPath)) {
            return null;
        }

        $imageInfo = getimagesize($fullPath);
        if (!$imageInfo) {
            return null;
        }

        // Create thumbnail directory
        $thumbnailDir = dirname($filepath) . '/thumbnails/';
        $fullThumbnailDir = self::getUploadPath($thumbnailDir);
        if (!is_dir($fullThumbnailDir)) {
            mkdir($fullThumbnailDir, 0755, true);
        }

        $thumbnailPath = $thumbnailDir . 'thumb_' . basename($filepath);

        // Create thumbnail based on image type
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($fullPath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($fullPath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($fullPath);
                break;
            default:
                return null;
        }

        if (!$source) {
            return null;
        }

        // Calculate thumbnail dimensions
        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);

        $ratio = min($width / $srcWidth, $height / $srcHeight);
        $thumbWidth = (int)($srcWidth * $ratio);
        $thumbHeight = (int)($srcHeight * $ratio);

        $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);

        // Preserve transparency for PNG/GIF
        if ($imageInfo[2] === IMAGETYPE_PNG || $imageInfo[2] === IMAGETYPE_GIF) {
            imagecolortransparent($thumbnail, imagecolorallocatealpha($thumbnail, 0, 0, 0, 127));
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }

        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $srcWidth, $srcHeight);

        // Save thumbnail
        $fullThumbnailPath = self::getUploadPath($thumbnailPath);
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumbnail, $fullThumbnailPath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumbnail, $fullThumbnailPath);
                break;
            case IMAGETYPE_GIF:
                imagegif($thumbnail, $fullThumbnailPath);
                break;
        }

        imagedestroy($source);
        imagedestroy($thumbnail);

        return $thumbnailPath;
    }
}
