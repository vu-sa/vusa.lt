<?php

namespace App\Services;

use App\Models\Banner;
use App\Models\Calendar;
use App\Models\Content;
use App\Models\ContentPart;
use App\Models\Duty;
use App\Models\Form;
use App\Models\Institution;
use App\Models\News;
use App\Models\Page;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class FileUsageScanner
{
    /**
     * Scan for file usage across all TipTap-enabled models with caching
     */
    public function scanFileUsage(string $filePath): array
    {
        $startTime = microtime(true);

        // Build list of possible URL/path variants for robust searching (legacy + current)
        $normalizedUrl = $this->normalizeFileUrl($filePath);

        // Check cache first
        $cacheKey = $this->generateCacheKey($normalizedUrl);
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            // Verify cache is still valid (file hasn't changed)
            $fileMetadata = $this->getFileMetadata($normalizedUrl);
            $cacheValid = $this->isCacheValid($cached, $fileMetadata);

            if ($cacheValid) {
                $this->logPerformance($startTime, $normalizedUrl, 'cache_hit', 0);

                return $cached;
            }
        }

        // Cache miss or invalid - perform full scan
        $variants = $this->buildSearchVariants($filePath, $normalizedUrl);

        // Get file metadata for verification
        $fileMetadata = $this->getFileMetadata($normalizedUrl);

        // Improve search accuracy for generic filenames
        $targetedVariants = $this->buildTargetedVariants($variants, $fileMetadata);

        // Use early termination strategy for better performance
        $earlyTermination = $this->shouldUseEarlyTermination($fileMetadata);

        // Progressive scanning with early termination
        $usage = [];
        $totalFound = 0;
        $maxResults = $earlyTermination ? 5 : PHP_INT_MAX; // Limit results for generic files

        // Use progressive variant scanning for better performance
        $progressiveVariants = $this->getProgressiveVariants($targetedVariants, $earlyTermination);

        foreach ($progressiveVariants as $variantSet) {
            if ($totalFound >= $maxResults) {
                break;
            }

            // Rich Content System (ContentPart) - highest priority
            if (! isset($usage['contentParts'])) {
                $usage['contentParts'] = $this->scanContentParts($variantSet, $fileMetadata);
                $totalFound += $usage['contentParts']->count();
                if ($totalFound >= $maxResults) {
                    break;
                }
            }

            // Image fields (likely to contain the file)
            if (! isset($usage['news'])) {
                $newsShort = $this->scanTextField(News::class, 'short', $variantSet, $fileMetadata);
                $newsImage = $this->scanTextField(News::class, 'image', $variantSet, $fileMetadata);
                $usage['news'] = $newsShort->merge($newsImage);
                $totalFound += $usage['news']->count();
                if ($totalFound >= $maxResults) {
                    break;
                }
            }

            if (! isset($usage['banners'])) {
                $usage['banners'] = $this->scanTextField(Banner::class, 'image_url', $variantSet, $fileMetadata);
                $totalFound += $usage['banners']->count();
                if ($totalFound >= $maxResults) {
                    break;
                }
            }

            // Institution images
            if (! isset($usage['institutions'])) {
                $instDesc = $this->scanTranslatableField(Institution::class, 'description', $variantSet, $fileMetadata);
                $instImage = $this->scanTextField(Institution::class, 'image_url', $variantSet, $fileMetadata);
                $instLogo = $this->scanTextField(Institution::class, 'logo_url', $variantSet, $fileMetadata);
                $usage['institutions'] = $instDesc->merge($instImage)->merge($instLogo);
                $totalFound += $usage['institutions']->count();
                if ($totalFound >= $maxResults) {
                    break;
                }
            }

            // Other models (lower priority)
            if (! isset($usage['calendar'])) {
                $usage['calendar'] = $this->scanTranslatableField(Calendar::class, 'description', $variantSet, $fileMetadata);
                $totalFound += $usage['calendar']->count();
            }

            if (! isset($usage['duties'])) {
                $usage['duties'] = $this->scanTranslatableField(Duty::class, 'description', $variantSet, $fileMetadata);
                $totalFound += $usage['duties']->count();
            }

            if (! isset($usage['types'])) {
                $usage['types'] = $this->scanTranslatableField(Type::class, 'description', $variantSet, $fileMetadata);
                $totalFound += $usage['types']->count();
            }

            if (! isset($usage['forms'])) {
                $usage['forms'] = $this->scanTranslatableField(Form::class, 'description', $variantSet, $fileMetadata);
                $totalFound += $usage['forms']->count();
            }

            // User profile images
            if (! isset($usage['users'])) {
                $usage['users'] = $this->scanTextField(User::class, 'profile_photo_path', $variantSet, $fileMetadata);
                $totalFound += $usage['users']->count();
            }

            if (! isset($usage['dutiables'])) {
                $usage['dutiables'] = $this->scanTextField(Dutiable::class, 'additional_photo', $variantSet, $fileMetadata);
                $totalFound += $usage['dutiables']->count();
            }
        }

        // Fill in empty collections for models that weren't scanned
        $allModelTypes = ['contentParts', 'calendar', 'news', 'banners', 'duties', 'institutions', 'types', 'forms', 'users', 'dutiables'];
        foreach ($allModelTypes as $modelType) {
            $usage[$modelType] ??= new Collection;
        }

        $result = $this->processUsageResults($usage, $normalizedUrl, $fileMetadata);

        // Cache the result for future use
        $this->cacheResult($cacheKey, $result, $fileMetadata);

        // Log performance metrics
        $this->logPerformance($startTime, $normalizedUrl, 'full_scan', $totalFound);

        return $result;
    }

    /**
     * Improve search accuracy for generic filenames by reordering variants by specificity
     */
    private function buildTargetedVariants(array $variants, array $fileMetadata): array
    {
        $filename = $fileMetadata['filename'] ?? '';
        $isGenericFilename = preg_match('/^\d+\.(jpg|jpeg|png|gif|pdf|doc|docx)$/i', $filename);

        if ($isGenericFilename) {
            // For generic filenames, sort by specificity (path length)
            usort($variants, function ($a, $b) use ($filename) {
                // Exact filename match gets lowest priority
                if ($a === $filename) {
                    return 1;
                }
                if ($b === $filename) {
                    return -1;
                }

                // Longer paths (more specific) get higher priority
                $aPathLength = substr_count($a, '/');
                $bPathLength = substr_count($b, '/');

                if ($aPathLength !== $bPathLength) {
                    return $bPathLength - $aPathLength; // Descending order
                }

                // Same path length, prefer longer strings
                return strlen($b) - strlen($a);
            });
        }

        return $variants;
    }

    /**
     * Try exact matches first (most efficient)
     */
    /**
     * Scan soft-deletable models with their trashed rows included.
     *
     * A file referenced only by a deleted banner, article or event is *not* unused:
     * `is_safe_to_delete` drives a "safe to delete" badge, and removing the file from
     * storage would leave the record permanently broken once it is restored.
     *
     * @param  class-string<Model>  $modelClass
     * @return Builder<Model>
     */
    private static function queryIncludingTrashed(string $modelClass): Builder
    {
        $query = $modelClass::query();

        // `withTrashed()` only exists on soft-deletable models; removing the global
        // scope is the same operation and is typed on the base builder.
        return in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)
            ? $query->withoutGlobalScope(SoftDeletingScope::class)
            : $query;
    }

    private function tryExactMatches(string $modelClass, string $field, array $variants): Collection
    {
        // For simple image URL fields, try exact matches
        $exactVariants = array_filter($variants,
            // Skip variants with wildcards or complex patterns
            fn ($variant) => ! str_contains($variant, '%') && ! str_contains($variant, '\\') && strlen($variant) > 3);

        if (empty($exactVariants)) {
            return new Collection;
        }

        return self::queryIncludingTrashed($modelClass)->whereIn($field, $exactVariants)->get();
    }

    /**
     * Execute a single LIKE query covering every variant, in both plain and
     * JSON-escaped form, with a result limit to prevent huge result sets.
     */
    private function executeLikeQuery(string $modelClass, string $field, array $variants): Collection
    {
        $needles = $this->likeNeedles($variants);

        if ($needles === []) {
            return new Collection;
        }

        return self::queryIncludingTrashed($modelClass)
            ->where(function (Builder $query) use ($field, $needles): void {
                foreach ($needles as $needle) {
                    $query->orWhereRaw($field." LIKE ? ESCAPE '|'", ['%'.$needle.'%']);
                }
            })
            ->limit(10)
            ->get();
    }

    /**
     * Get file metadata for verification purposes with caching
     */
    private function getFileMetadata(string $url): array
    {
        $cacheKey = 'file_metadata:'.md5($url);

        // Try cache first (short TTL for metadata)
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Calculate metadata
        $publicPath = $this->convertUrlToPublicPath($url);
        $absoluteStoragePath = storage_path('app/'.$publicPath);
        $fileExists = file_exists($absoluteStoragePath);

        $metadata = [
            'exists' => $fileExists,
            'size' => $fileExists ? @filesize($absoluteStoragePath) : null,
            'sha256' => $fileExists ? @hash_file('sha256', $absoluteStoragePath) : null,
            'md5' => $fileExists ? @hash_file('md5', $absoluteStoragePath) : null,
            'filename' => basename($url),
            'public_path' => $publicPath,
            'absolute_path' => $absoluteStoragePath,
        ];

        // Cache metadata for 10 minutes
        Cache::put($cacheKey, $metadata, 600);

        return $metadata;
    }

    /**
     * Build a smart, prioritized list of search variants with early exit optimization
     */
    private function buildSearchVariants(string $originalPath, string $normalizedUrl): array
    {
        $variants = [];
        $push = function ($value) use (&$variants): void {
            if ($value !== null && $value !== '' && ! in_array($value, $variants, true)) {
                $variants[] = $value;
            }
        };

        // Priority 1: Most common and likely patterns first
        $this->addStructuralVariants($push, $originalPath, $normalizedUrl);

        // Priority 1b: fully-qualified vusa.lt URLs (https://static.vusa.lt/…)
        $this->addVusaDomainVariants($push, $variants);

        // Early exit for ASCII-only files (huge performance gain)
        $hasNonAscii = (bool) preg_match('/[^\x00-\x7F]/', $normalizedUrl);
        if (! $hasNonAscii) {
            return $variants; // Skip Unicode processing entirely
        }

        // Priority 2: Unicode variants (only for non-ASCII files)
        $this->addUnicodeVariants($push, $variants, $normalizedUrl);

        return $variants;
    }

    /**
     * Determine if early termination should be used based on file characteristics
     */
    private function shouldUseEarlyTermination(array $fileMetadata): bool
    {
        $filename = $fileMetadata['filename'] ?? '';

        // Use early termination for very generic filenames to avoid overwhelming results
        $isGenericFilename = preg_match('/^\d+\.(jpg|jpeg|png|gif|pdf|doc|docx)$/i', $filename);

        return $isGenericFilename;
    }

    /**
     * Get variants in progressive order for early termination scanning
     */
    private function getProgressiveVariants(array $allVariants, bool $earlyTermination): array
    {
        if (! $earlyTermination) {
            // For non-generic files, use all variants at once
            return [$allVariants];
        }

        // For generic files, try variants in order of specificity
        $progressive = [];

        // First pass: Most specific patterns (full paths)
        $specificVariants = array_filter($allVariants, fn ($variant) => str_contains($variant, '/') && ! str_contains($variant, '\\'));
        if (! empty($specificVariants)) {
            $progressive[] = $specificVariants;
        }

        // Second pass: Filename patterns with escaping
        $escapedVariants = array_filter($allVariants, fn ($variant) => str_contains($variant, '\\'));
        if (! empty($escapedVariants)) {
            $progressive[] = $escapedVariants;
        }

        // Final pass: All remaining patterns
        $remainingVariants = array_diff($allVariants, $specificVariants, $escapedVariants);
        if (! empty($remainingVariants)) {
            $progressive[] = $remainingVariants;
        }

        return $progressive;
    }

    /**
     * Generate cache key for file scan results
     */
    private function generateCacheKey(string $normalizedUrl): string
    {
        return 'file_usage_scan:'.md5($normalizedUrl);
    }

    /**
     * Check if cached result is still valid
     */
    private function isCacheValid(array $cached, array $fileMetadata): bool
    {
        // Cache is valid if file size and modification time haven't changed
        $cachedFileSize = $cached['file_size'] ?? null;
        $cachedScannedAt = $cached['scanned_at'] ?? null;

        $currentFileSize = $fileMetadata['size'] ?? null;

        // If file doesn't exist anymore, cache is invalid
        if (! $fileMetadata['exists']) {
            return false;
        }

        // If file size changed, cache is invalid
        if ($cachedFileSize !== $currentFileSize) {
            return false;
        }

        // Cache expires after 1 hour for active scanning
        if ($cachedScannedAt) {
            $scannedTime = Carbon::parse($cachedScannedAt);
            if ($scannedTime->diffInHours(now()) > 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Cache scan result with appropriate TTL
     */
    private function cacheResult(string $cacheKey, array $result, array $fileMetadata): void
    {
        $filename = $fileMetadata['filename'] ?? '';
        $isGenericFilename = preg_match('/^\d+\.(jpg|jpeg|png|gif|pdf|doc|docx)$/i', $filename);

        // Cache generic files for shorter time (they change more often)
        // Cache specific files for longer time (they're more stable)
        $ttl = $isGenericFilename ? 300 : 3600; // 5 minutes vs 1 hour

        Cache::put($cacheKey, $result, $ttl);
    }

    /**
     * Clear cache for a specific file (useful when files are updated/deleted)
     */
    public function clearFileCache(string $filePath): void
    {
        $normalizedUrl = $this->normalizeFileUrl($filePath);
        $scanCacheKey = $this->generateCacheKey($normalizedUrl);
        $metadataCacheKey = 'file_metadata:'.md5($normalizedUrl);

        Cache::forget($scanCacheKey);
        Cache::forget($metadataCacheKey);
    }

    /**
     * Clear all file scanner caches (for maintenance)
     */
    public function clearAllCache(): void
    {
        // Unfortunately Laravel doesn't have a great way to delete by prefix
        // This would require Redis/Memcached with pattern support
        Log::info('FileUsageScanner: Manual cache clearing requested');
    }

    /**
     * Log performance metrics for monitoring
     */
    private function logPerformance(float $startTime, string $url, string $scanType, int $resultsFound): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2); // milliseconds
        $filename = basename($url);

        // Only log slow scans or cache misses for monitoring
        if ($duration > 100 || $scanType === 'full_scan') {
            Log::info('FileUsageScanner Performance', [
                'filename' => $filename,
                'scan_type' => $scanType,
                'duration_ms' => $duration,
                'results_found' => $resultsFound,
                'is_slow' => $duration > 500,
            ]);
        }
    }

    /**
     * Add structural path variants (highest priority)
     */
    private function addStructuralVariants(callable $push, string $originalPath, string $normalizedUrl): void
    {
        // Most common patterns first (order matters for performance)
        $push($normalizedUrl);                              // /uploads/files/news/image.jpg
        $push(ltrim($normalizedUrl, '/'));                 // uploads/files/news/image.jpg

        // Legacy path transformations
        if (str_contains($normalizedUrl, '/uploads/files/')) {
            $legacy = str_replace('/uploads/files/', '/uploads/', $normalizedUrl);
            $push($legacy);                                 // /uploads/news/image.jpg
            $push(ltrim($legacy, '/'));                    // uploads/news/image.jpg
        }

        // Storage symlink variants
        if (str_starts_with($normalizedUrl, '/uploads/files/')) {
            $storageVariant = str_replace('/uploads/files/', '/storage/files/', $normalizedUrl);
            $push($storageVariant);                        // /storage/files/news/image.jpg
            $push(ltrim($storageVariant, '/'));           // storage/files/news/image.jpg
        }

        // Public path variants
        if (str_starts_with($originalPath, 'public/')) {
            $push('/'.str_replace('public/', 'uploads/', $originalPath));  // /uploads/files/news/image.jpg
            $push($originalPath);                                          // public/files/news/image.jpg
        }

        // Filename-only (put last as it's least specific)
        $filename = basename($normalizedUrl);
        $push($filename);                                  // image.jpg
    }

    /**
     * Add variants for fully-qualified vusa.lt URLs (https://static.vusa.lt/…),
     * which content sometimes stores instead of site-relative paths.
     *
     * The bare "vusa.lt" + path substring matches any scheme (http/https///)
     * and any vusa.lt subdomain — static, www, tenant — while never matching a
     * foreign host. Only vusa.lt domains get this treatment: an absolute URL
     * on another host points at a different copy of the file, not this one.
     */
    private function addVusaDomainVariants(callable $push, array $variants): void
    {
        foreach ($variants as $variant) {
            if (str_starts_with($variant, '/')) {
                $push('vusa.lt'.$variant);
            }
        }
    }

    /**
     * Add Unicode-specific variants (only for non-ASCII files)
     */
    private function addUnicodeVariants(callable $push, array $baseVariants, string $normalizedUrl): void
    {
        // 1. Unicode normalization variants
        if (class_exists(\Normalizer::class)) {
            foreach ([\Normalizer::FORM_C, \Normalizer::FORM_D] as $form) {
                $norm = \Normalizer::normalize($normalizedUrl, $form);
                if ($norm && $norm !== $normalizedUrl) {
                    $push($norm);
                    $push(ltrim($norm, '/'));
                    $push(basename($norm));
                }
            }
        }

        // JSON slash/codepoint escapes are derived at query time (see likeNeedles()).
        $this->addUnicodeCodepointEscapes($push, $baseVariants);
    }

    /**
     * Add Unicode codepoint escape variants (most expensive, only when needed)
     */
    private function addUnicodeCodepointEscapes(callable $push, array $variants): void
    {
        // Only escape combining marks (most common case)
        $combiningEscaped = [];
        foreach ($variants as $v) {
            if (! preg_match('/\p{M}/u', $v)) {
                continue; // Skip if no combining marks
            }

            $escaped = preg_replace_callback('/(\p{M})/u', function ($m) {
                $cp = strtoupper(dechex(mb_ord($m[0], 'UTF-8')));

                return '\\u'.str_pad($cp, 4, '0', STR_PAD_LEFT);
            }, $v);

            if ($escaped && $escaped !== $v) {
                $combiningEscaped[] = $escaped;
                // Also add slash-escaped version
                $slashEsc = str_replace('/', '\\/', $escaped);
                if ($slashEsc !== $escaped) {
                    $combiningEscaped[] = $slashEsc;
                }
            }
        }

        foreach ($combiningEscaped as $cv) {
            $push($cv);
        }

        // Full codepoint escape (fallback for edge cases)
        if (function_exists('mb_ord')) {
            foreach ($variants as $v) {
                if (! preg_match('/[^\x00-\x7F]/', $v)) {
                    continue; // ASCII-only, skip
                }

                $fullEscaped = preg_replace_callback('/[\x{80}-\x{10FFFF}]/u', function ($m) {
                    $cp = strtolower(dechex(mb_ord($m[0], 'UTF-8')));

                    return '\\u'.str_pad($cp, 4, '0', STR_PAD_LEFT);
                }, $v);

                if ($fullEscaped && $fullEscaped !== $v) {
                    $push($fullEscaped);
                    $slashEsc = str_replace('/', '\\/', $fullEscaped);
                    if ($slashEsc !== $fullEscaped) {
                        $push($slashEsc);
                    }
                }
            }
        }
    }

    /**
     * Scan multiple files for usage (batch operation)
     */
    public function scanMultipleFiles(array $filePaths): array
    {
        $results = [];

        foreach ($filePaths as $filePath) {
            $results[$filePath] = $this->scanFileUsage($filePath);
        }

        return $results;
    }

    /**
     * Normalize file path to URL format for searching
     */
    private function normalizeFileUrl(string $filePath): string
    {
        // Convert from "public/files/..." to "/uploads/files/..."
        if (str_starts_with($filePath, 'public/')) {
            return '/'.str_replace('public/', 'uploads/', $filePath);
        }

        // If already in URL format, return as is
        if (str_starts_with($filePath, '/uploads/')) {
            return $filePath;
        }

        // Default case - assume it's a relative path from uploads
        return '/uploads/'.ltrim($filePath, '/');
    }

    /**
     * Scan ContentPart models for file references inside TipTap JSON.
     *
     * A single LIKE query covers every variant: json_content is stored as
     * serialized JSON text where '/' reads '\/', so each variant is matched in
     * both plain and JSON-escaped form (see jsonNeedles()).
     */
    private function scanContentParts(string|array $urlOrVariants, array $fileMetadata = []): Collection
    {
        try {
            $variants = is_array($urlOrVariants) ? $urlOrVariants : [$urlOrVariants];
            $needles = $this->jsonNeedles($variants);

            if ($needles === []) {
                return new Collection;
            }

            return ContentPart::query()
                ->whereIn('type', ['tiptap', 'shadcn-card', 'shadcn-accordion', 'hero'])
                ->where(function (Builder $query) use ($needles): void {
                    foreach ($needles as $needle) {
                        $query->orWhereRaw("json_content LIKE ? ESCAPE '|'", ['%'.$needle.'%']);
                    }
                })
                ->limit(10)
                ->with('content')
                ->get();

        } catch (\Exception $e) {
            Log::error('Error scanning ContentParts', [
                'variants' => $urlOrVariants,
                'error' => $e->getMessage(),
            ]);

            return new Collection;
        }
    }

    /**
     * Build LIKE patterns for a variant in both its plain and its JSON-encoded
     * form — serialized JSON stores '/' as '\/', so a stored '/uploads/a.jpg'
     * reference physically reads '\/uploads\/a.jpg'.
     *
     * @param  array<int, string>  $variants
     * @return array<int, string>
     */
    private function likeNeedles(array $variants): array
    {
        $needles = [];

        foreach ($variants as $variant) {
            if (strlen($variant) < 4) {
                continue;
            }

            foreach ($this->slashForms($variant) as $form) {
                $needle = $this->escapeLike($form);

                if (! in_array($needle, $needles, true)) {
                    $needles[] = $needle;
                }
            }
        }

        return $needles;
    }

    /**
     * Build LIKE patterns for JSON text columns.
     *
     * Stored values live inside JSON strings, so path references are anchored
     * at the opening quote — a foreign absolute URL that merely contains the
     * same path ("https://cdn.example.com/uploads/a.jpg") must not count as
     * usage of the local file. vusa.lt domain variants carry their own boundary
     * (the host), so they are also matched unanchored, covering absolute URLs
     * on any vusa.lt subdomain.
     *
     * @param  array<int, string>  $variants
     * @return array<int, string>
     */
    private function jsonNeedles(array $variants): array
    {
        $needles = [];

        foreach ($variants as $variant) {
            if (strlen($variant) < 4) {
                continue;
            }

            foreach ($this->slashForms($variant) as $form) {
                $needles[] = $this->escapeLike('"'.$form);
            }

            if (str_starts_with($variant, 'vusa.lt/')) {
                foreach ($this->slashForms($variant) as $form) {
                    $needles[] = $this->escapeLike($form);
                }
            }
        }

        return array_values(array_unique($needles));
    }

    /**
     * A variant and its JSON-serialized twin ('/' escaped as '\/').
     *
     * @return array<int, string>
     */
    private function slashForms(string $variant): array
    {
        $escaped = str_replace('/', '\\/', $variant);

        return $escaped === $variant ? [$variant] : [$variant, $escaped];
    }

    /**
     * Escape a LIKE pattern with '|' as the escape character, keeping '%', '_'
     * and '\' literal on both MySQL (default escape '\') and SQLite (none).
     */
    private function escapeLike(string $needle): string
    {
        return str_replace(['|', '%', '_'], ['||', '|%', '|_'], $needle);
    }

    /**
     * Scan translatable fields (JSON columns) with optimized query patterns
     */
    private function scanTranslatableField(string $modelClass, string $field, string|array $urlOrVariants, array $fileMetadata = []): Collection
    {
        try {
            $variants = is_array($urlOrVariants) ? $urlOrVariants : [$urlOrVariants];

            // Try exact matches first (more efficient than LIKE)
            $exactMatches = $this->tryExactMatches($modelClass, $field, $variants);
            if ($exactMatches->isNotEmpty()) {
                return $exactMatches;
            }

            // Fall back to optimized LIKE queries
            return $this->executeLikeQuery($modelClass, $field, $variants);

        } catch (\Exception $e) {
            Log::error('Error scanning translatable field', [
                'model' => $modelClass,
                'field' => $field,
                'variants' => $urlOrVariants,
                'error' => $e->getMessage(),
            ]);

            return new Collection;
        }
    }

    /**
     * Scan regular text fields with optimized query patterns
     */
    private function scanTextField(string $modelClass, string $field, string|array $urlOrVariants, array $fileMetadata = []): Collection
    {
        try {
            $variants = is_array($urlOrVariants) ? $urlOrVariants : [$urlOrVariants];

            // Try exact matches first (more efficient than LIKE)
            $exactMatches = $this->tryExactMatches($modelClass, $field, $variants);
            if ($exactMatches->isNotEmpty()) {
                return $exactMatches;
            }

            // Fall back to optimized LIKE queries
            return $this->executeLikeQuery($modelClass, $field, $variants);

        } catch (\Exception $e) {
            Log::error('Error scanning text field', [
                'model' => $modelClass,
                'field' => $field,
                'variants' => $urlOrVariants,
                'error' => $e->getMessage(),
            ]);

            return new Collection;
        }
    }

    /**
     * Process usage results into a structured format
     */
    private function processUsageResults(array $usage, string $url, array $fileMetadata = []): array
    {
        $usageDetails = [];
        $totalUsages = 0;
        $rawPartMatches = [];

        // Use provided file metadata or calculate if not provided
        if (! empty($fileMetadata)) {
            $fileExists = $fileMetadata['exists'];
            $filesize = $fileMetadata['size'];
            $hashSha256 = $fileMetadata['sha256'];
            $hashMd5 = $fileMetadata['md5'];
        } else {
            // Fallback: calculate metadata if not provided
            $publicPath = $this->convertUrlToPublicPath($url);
            $absoluteStoragePath = storage_path('app/'.$publicPath);
            $fileExists = file_exists($absoluteStoragePath);
            $filesize = $fileExists ? @filesize($absoluteStoragePath) : null;
            $hashSha256 = $fileExists ? @hash_file('sha256', $absoluteStoragePath) : null;
            $hashMd5 = $fileExists ? @hash_file('md5', $absoluteStoragePath) : null;
        }

        foreach ($usage as $modelType => $results) {
            if ($results->isEmpty()) {
                continue;
            }

            // Special aggregation for ContentParts – map to owning model (Page, News, Tenant)
            if ($modelType === 'contentParts') {
                $groupedByContent = $results->groupBy('content_id');
                foreach ($groupedByContent as $contentId => $parts) {
                    $owner = $this->resolvePrimaryOwnerForContent($contentId);
                    $ownerModelType = $owner ? strtolower(class_basename($owner)) : 'content';
                    $usageDetails[] = [
                        'model_type' => $ownerModelType,
                        'model_class' => $owner ? $owner::class : Content::class,
                        'id' => $owner->id ?? $contentId,
                        'title' => $owner ? $this->getModelTitle($owner) : ('Content #'.$contentId),
                        'url' => $owner ? $this->getModelAdminUrl($owner) : null,
                        'edit_url' => $owner ? $this->getModelAdminEditUrl($owner) : null,
                        'created_at' => $owner->created_at ?? null,
                        'updated_at' => $owner->updated_at ?? null,
                        'matched_parts' => $parts->pluck('id')->all(),
                        'matched_parts_count' => $parts->count(),
                    ];
                    $totalUsages++; // count unique owners / content containers
                    $rawPartMatches = array_merge($rawPartMatches, $parts->pluck('id')->all());
                }

                continue;
            }

            // Default (non-aggregated) handling for other models
            foreach ($results as $result) {
                $usageDetails[] = [
                    'model_type' => $modelType,
                    'model_class' => $result::class,
                    'id' => $result->id,
                    'title' => $this->getModelTitle($result),
                    'url' => $this->getModelAdminUrl($result),
                    'edit_url' => $this->getModelAdminEditUrl($result),
                    'created_at' => $result->created_at ?? null,
                    'updated_at' => $result->updated_at ?? null,
                ];
                $totalUsages++;
            }
        }

        return [
            'file_url' => $url,
            // total_usages now counts distinct owning models/records
            'total_usages' => $totalUsages,
            'is_safe_to_delete' => $totalUsages === 0,
            'file_exists' => $fileExists,
            'file_size' => $filesize,
            'file_hash_sha256' => $hashSha256,
            'file_hash_md5' => $hashMd5,
            'usage_details' => $usageDetails,
            'scanned_models' => array_keys($usage),
            'raw_part_matches' => $rawPartMatches, // for debugging if needed
            'scanned_at' => now()->toISOString(),
        ];
    }

    /**
     * Convert a normalized URL (/uploads/files/...) back to a storage public path (public/files/...).
     */
    private function convertUrlToPublicPath(string $url): string
    {
        // Expecting patterns like /uploads/files/... -> public/files/...
        if (str_starts_with($url, '/uploads/')) {
            return 'public/'.ltrim(str_replace('/uploads/', '', $url), '/');
        }

        // Already a relative path? Leave as-is; caller prepends storage_path('app/').
        return ltrim($url, '/');
    }

    /**
     * Resolve primary owning model for given content_id.
     * Priority order: Page, News, Tenant (extendable later).
     *
     * No caching: callers iterate groups keyed by content_id, so every id is
     * resolved exactly once per scan anyway, and a static cache would leak
     * stale owners between tests (RefreshDatabase reuses auto-increment ids).
     */
    private function resolvePrimaryOwnerForContent(int $contentId): ?object
    {
        return Page::where('content_id', $contentId)->first()
            ?? News::where('content_id', $contentId)->first()
            ?? Tenant::where('content_id', $contentId)->first();
    }

    /**
     * Get a meaningful title for a model
     */
    private function getModelTitle($model): string
    {
        // Try common title fields
        $titleFields = ['title', 'name', 'subject', 'description'];

        foreach ($titleFields as $field) {
            if (isset($model->$field)) {
                $value = $model->$field;

                // Handle translatable fields
                if (is_array($value)) {
                    return $value['lt'] ?? $value['en'] ?? json_encode($value);
                }

                if (is_string($value) && ! empty(trim($value))) {
                    return trim(strip_tags($value));
                }
            }
        }

        // Fallback to model class and ID
        return class_basename($model).' #'.$model->id;
    }

    /**
     * Generate admin URL for a model (if applicable)
     */
    private function getModelAdminUrl($model): ?string
    {
        try {
            $modelName = strtolower(class_basename($model));

            // Handle special cases
            $routeNames = [
                'calendar' => 'calendar',
                'news' => 'news',
                'duty' => 'duties',
                'institution' => 'institutions',
                'type' => 'types',
                'form' => 'forms',
                'dutiable' => 'dutiables',
                'page' => 'pages',
                'tenant' => 'tenants',
                'contentpart' => null, // ContentParts don't have direct admin URLs
            ];

            $routeName = $routeNames[$modelName] ?? null;

            if ($routeName && $model->id) {
                return route($routeName.'.show', $model->id);
            }

            return null;
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Generate admin edit URL for a model if edit route exists.
     */
    private function getModelAdminEditUrl($model): ?string
    {
        try {
            $modelName = strtolower(class_basename($model));
            $routeNames = [
                'calendar' => 'calendar',
                'news' => 'news',
                'duty' => 'duties',
                'institution' => 'institutions',
                'type' => 'types',
                'form' => 'forms',
                'dutiable' => 'dutiables',
                'page' => 'pages',
                'tenant' => 'tenants',
            ];

            $base = $routeNames[$modelName] ?? null;
            if ($base && $model->id && Route::has($base.'.edit')) {
                return route($base.'.edit', $model->id);
            }

            return null;
        } catch (\Exception) {
            return null;
        }
    }
}
