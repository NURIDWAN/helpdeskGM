<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileCompressionService
{
    /**
     * Compress and store file
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string $fileName
     * @param int $quality (0-100, default 75)
     * @param int $maxWidth (optional, for resizing large images)
     * @return string file path
     */
    public function compressAndStore(
        UploadedFile $file,
        string $directory,
        string $fileName,
        int $quality = 75,
        int $maxWidth = 1920
    ): string {
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        // Check if file is an image that can be compressed
        $compressibleImages = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

        if (in_array($mimeType, $compressibleImages)) {
            return $this->compressImage($file, $directory, $fileName, $quality, $maxWidth);
        }

        // For non-image files or unsupported image types, store as is
        return $file->storeAs($directory, $fileName, 'public');
    }

    /**
     * Compress image file using GD library
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string $fileName
     * @param int $quality
     * @param int $maxWidth
     * @return string file path
     */
    private function compressImage(
        UploadedFile $file,
        string $directory,
        string $fileName,
        int $quality,
        int $maxWidth
    ): string {
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        // Create image resource from uploaded file
        $imageResource = match($mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($file->getRealPath()),
            'image/png' => imagecreatefrompng($file->getRealPath()),
            'image/webp' => imagecreatefromwebp($file->getRealPath()),
            default => null
        };

        if ($imageResource === false || $imageResource === null) {
            // If can't create image resource, store original file
            return $file->storeAs($directory, $fileName, 'public');
        }

        // Get original dimensions
        $originalWidth = imagesx($imageResource);
        $originalHeight = imagesy($imageResource);

        // Calculate new dimensions if image is too large
        $newWidth = $originalWidth;
        $newHeight = $originalHeight;

        if ($originalWidth > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int)($originalHeight * ($maxWidth / $originalWidth));
        }

        // Create new image with new dimensions
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and WebP
        if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resize image
        imagecopyresampled(
            $newImage,
            $imageResource,
            0, 0, 0, 0,
            $newWidth,
            $newHeight,
            $originalWidth,
            $originalHeight
        );

        // Create temporary file path
        $tempPath = sys_get_temp_dir() . '/' . $fileName;

        // Save compressed image to temporary file
        $saved = match($mimeType) {
            'image/jpeg', 'image/jpg' => imagejpeg($newImage, $tempPath, $quality),
            'image/png' => imagepng($newImage, $tempPath, (int)(9 - ($quality / 10))), // PNG compression is 0-9
            'image/webp' => imagewebp($newImage, $tempPath, $quality),
            default => false
        };

        // Free up memory
        imagedestroy($imageResource);
        imagedestroy($newImage);

        if (!$saved) {
            // If compression failed, store original file
            return $file->storeAs($directory, $fileName, 'public');
        }

        // Store the compressed file
        $filePath = $directory . '/' . $fileName;
        Storage::disk('public')->put($filePath, file_get_contents($tempPath));

        // Delete temporary file
        @unlink($tempPath);

        return $filePath;
    }

    /**
     * Get file size in human readable format
     *
     * @param string $filePath
     * @return string
     */
    public function getFileSize(string $filePath): string
    {
        $size = Storage::disk('public')->size($filePath);

        if ($size < 1024) {
            return $size . ' B';
        } elseif ($size < 1048576) {
            return round($size / 1024, 2) . ' KB';
        } else {
            return round($size / 1048576, 2) . ' MB';
        }
    }
}
