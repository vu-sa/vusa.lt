<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ImageUploadService
{
    /**
     * Default image processing options
     */
    protected array $defaultOptions = [
        'maxWidth' => 1600,
        'quality' => 75,
        'format' => 'webp',
    ];

    public function __construct(protected array $options = [])
    {
        $this->options = array_merge($this->defaultOptions, $options);
    }

    /**
     * Process an image file: scale down and convert to WebP
     *
     * @param  UploadedFile|string  $source  File upload or data URL/path
     * @return array{image: \Intervention\Image\Interfaces\ImageInterface|\Intervention\Image\Interfaces\EncodedImageInterface, originalSize: int}
     */
    public function processImage(UploadedFile|string $source, array $options = []): array
    {
        $opts = array_merge($this->options, $options);

        // Get original size for compression statistics
        $originalSize = $source instanceof UploadedFile
            ? ($source->getSize() ?: 0)
            : strlen($source);

        // Read and process image
        $image = Image::read($source);
        $image = $image->scaleDown(width: $opts['maxWidth']);

        // Convert to desired format
        if ($opts['format'] === 'webp') {
            $image = $image->toWebp($opts['quality']);
        } elseif ($opts['format'] === 'jpeg' || $opts['format'] === 'jpg') {
            $image = $image->toJpeg($opts['quality']);
        } elseif ($opts['format'] === 'png') {
            $image = $image->toPng();
        }

        return [
            'image' => $image,
            'originalSize' => $originalSize,
        ];
    }

    /**
     * Process and save an image to storage
     *
     * @return array{url: string, name: string, originalSize: int, compressedSize: int, compressionRatio: int}
     */
    public function processAndSave(
        UploadedFile|string $source,
        string $directory,
        ?string $filename = null,
        array $options = []
    ): array {
        $opts = array_merge($this->options, $options);

        // Get original filename
        if (! $filename) {
            $filename = $source instanceof UploadedFile
                ? $source->getClientOriginalName()
                : 'image.jpg';
        }

        // Process image
        $result = $this->processImage($source, $opts);
        $image = $result['image'];
        $originalSize = $result['originalSize'];

        // Get processed filename with correct extension
        $processedName = pathinfo($filename, PATHINFO_FILENAME).'.'.$opts['format'];

        // Ensure directory exists
        $fullDirectoryPath = $this->normalizeDirectoryPath($directory);
        if (! Storage::exists($fullDirectoryPath)) {
            Storage::makeDirectory($fullDirectoryPath);
        }

        // Check if file exists and rename if needed
        if (Storage::exists($fullDirectoryPath.'/'.$processedName)) {
            $processedName = time().'_'.$processedName;
        }

        // Save the processed image
        $fullPath = storage_path('app/'.$fullDirectoryPath.'/'.$processedName);
        $image->save($fullPath);

        // Get compressed file size
        $compressedSize = filesize($fullPath);
        $compressionRatio = $originalSize > 0
            ? round((1 - $compressedSize / $originalSize) * 100)
            : 0;

        // Generate URL
        $url = $this->generateUrl($directory, $processedName);

        Log::info('Image processed and saved', [
            'original_name' => $filename,
            'processed_name' => $processedName,
            'directory' => $directory,
            'original_size' => $originalSize,
            'compressed_size' => $compressedSize,
            'compression_ratio' => $compressionRatio,
        ]);

        return [
            'url' => $url,
            'name' => $processedName,
            'path' => $fullDirectoryPath.'/'.$processedName,
            'originalSize' => $originalSize,
            'compressedSize' => $compressedSize,
            'compressionRatio' => max(0, $compressionRatio),
        ];
    }

    /**
     * Normalize directory path for storage
     */
    protected function normalizeDirectoryPath(string $directory): string
    {
        // Remove any path traversal attempts
        $directory = str_replace(['../', '..\\'], '', $directory);

        // Handle different path patterns
        if (str_starts_with($directory, 'public/')) {
            return $directory;
        }

        if (str_starts_with($directory, 'files/')) {
            return 'public/'.$directory;
        }

        // Simple folder name (e.g., 'banners', 'news')
        if (! str_contains($directory, '/')) {
            return 'public/'.$directory;
        }

        return 'public/'.$directory;
    }

    /**
     * Generate public URL for a stored image
     */
    protected function generateUrl(string $directory, string $filename): string
    {
        // Handle different path patterns
        if (str_starts_with($directory, 'public/')) {
            $urlPath = str_replace('public/', '', $directory);
        } elseif (str_starts_with($directory, 'files/')) {
            $urlPath = $directory;
        } else {
            $urlPath = $directory;
        }

        return '/uploads/'.$urlPath.'/'.$filename;
    }

    /**
     * Get human-readable file size
     */
    public static function formatFileSize(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $k = 1024;
        $sizes = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes) / log($k));

        return round($bytes / pow($k, $i), 1).' '.$sizes[$i];
    }

    /**
     * Create a short display name for long filenames
     */
    public static function shortenFilename(string $filename, int $maxLength = 20): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);

        if (strlen($nameWithoutExt) <= $maxLength) {
            return $filename;
        }

        return substr($nameWithoutExt, 0, $maxLength).'...'.'.'.$extension;
    }
}
