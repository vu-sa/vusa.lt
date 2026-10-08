<?php

namespace App\Services;

use App\Support\Media\ImageConversions;
use App\Support\StagingProtection;
use App\Support\StoragePath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Format;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Laravel\Facades\Image;

class ImageUploadService
{
    protected array $defaultOptions = [
        'maxWidth' => 1600,
        'quality' => 75,
        'format' => 'webp',
    ];

    public function __construct(protected FileUploadWriter $writer, protected array $options = [])
    {
        $this->options = array_merge($this->defaultOptions, $options);
    }

    /** @return array{image: ImageInterface|EncodedImageInterface, originalSize: int} */
    public function processImage(UploadedFile|string $source, array $options = []): array
    {
        $opts = array_merge($this->options, $options);

        $originalSize = $source instanceof UploadedFile
            ? ($source->getSize() ?: 0)
            : strlen($source);

        $image = Image::decode($source);
        $image = $image->scaleDown(width: $opts['maxWidth']);

        if ($opts['format'] === 'webp') {
            $image = $image->encodeUsingFormat(Format::WEBP, quality: $opts['quality']);
        } elseif ($opts['format'] === 'jpeg' || $opts['format'] === 'jpg') {
            $image = $image->encodeUsingFormat(Format::JPEG, quality: $opts['quality']);
        } elseif ($opts['format'] === 'png') {
            $image = $image->encodeUsingFormat(Format::PNG);
        }

        return [
            'image' => $image,
            'originalSize' => $originalSize,
        ];
    }

    /**
     * A media original: longest edge bounded, WebP. A WebP already within bounds keeps its bytes,
     * since re-encoding it only loses quality.
     *
     * @return array{contents: string, width: int, height: int}
     */
    public function normalize(UploadedFile|string $source, int $maxEdge = ImageConversions::ORIGINAL_MAX): array
    {
        $contents = $source instanceof UploadedFile ? (string) file_get_contents($source->getRealPath()) : $source;
        $size = @getimagesizefromstring($contents);

        if ($size !== false && $size['mime'] === 'image/webp' && max($size[0], $size[1]) <= $maxEdge) {
            return ['contents' => $contents, 'width' => $size[0], 'height' => $size[1]];
        }

        $image = Image::decode($contents)->scaleDown(width: $maxEdge, height: $maxEdge);

        return [
            'contents' => (string) $image->encodeUsingFormat(Format::WEBP, quality: 82),
            'width' => $image->width(),
            'height' => $image->height(),
        ];
    }

    /** @return array{url: string, name: string, path: string, renamed: bool, originalSize: int, compressedSize: int, compressionRatio: int} */
    public function processAndSave(
        UploadedFile|string $source,
        string $directory,
        ?string $filename = null,
        array $options = []
    ): array {
        StagingProtection::ensureFilesAreWritable();

        $opts = array_merge($this->options, $options);

        if (! $filename) {
            $filename = $source instanceof UploadedFile
                ? $source->getClientOriginalName()
                : 'image.jpg';
        }

        $result = $this->processImage($source, $opts);
        $image = $result['image'];
        $originalSize = $result['originalSize'];

        $processedName = pathinfo($filename, PATHINFO_FILENAME).'.'.$opts['format'];

        $fullDirectoryPath = $this->normalizeDirectoryPath($directory);
        $encoded = $image instanceof EncodedImageInterface ? $image : $image->encode();
        $contents = (string) $encoded;
        $stored = $this->writer->write($fullDirectoryPath, $processedName, $contents);
        $processedName = $stored['name'];

        $compressedSize = strlen($contents);
        $compressionRatio = $originalSize > 0
            ? round((1 - $compressedSize / $originalSize) * 100)
            : 0;

        Log::info('Image processed and saved', [
            'original_name' => $filename,
            'processed_name' => $processedName,
            'directory' => $directory,
            'original_size' => $originalSize,
            'compressed_size' => $compressedSize,
            'compression_ratio' => $compressionRatio,
        ]);

        return [
            ...$stored,
            'originalSize' => $originalSize,
            'compressedSize' => $compressedSize,
            'compressionRatio' => max(0, $compressionRatio),
        ];
    }

    protected function normalizeDirectoryPath(string $directory): string
    {
        if (StoragePath::hasTraversal($directory) || ! StoragePath::isSafeRelative($directory)) {
            throw new \InvalidArgumentException('Invalid image directory');
        }

        $directory = StoragePath::normalizeRelative($directory);

        if (str_starts_with($directory, 'public/')) {
            return $directory;
        }

        return 'public/'.$directory;
    }

    public static function formatFileSize(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $k = 1024;
        $sizes = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes) / log($k));

        return round($bytes / $k ** $i, 1).' '.$sizes[$i];
    }

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
