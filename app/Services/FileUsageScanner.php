<?php

namespace App\Services;

use App\Models\Calendar;
use App\Models\ContentPart;
use App\Models\Duty;
use App\Models\Form;
use App\Models\Institution;
use App\Models\News;
use App\Models\Training;
use App\Models\Type;
use Illuminate\Database\Eloquent\Collection;
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
                $usage['banners'] = $this->scanTextField(\App\Models\Banner::class, 'image_url', $variantSet, $fileMetadata);
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

            if (! isset($usage['trainings'])) {
                $trainDesc = $this->scanTranslatableField(Training::class, 'description', $variantSet, $fileMetadata);
                $trainImage = $this->scanTextField(Training::class, 'image', $variantSet, $fileMetadata);
                $usage['trainings'] = $trainDesc->merge($trainImage);
                $totalFound += $usage['trainings']->count();
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
                $usage['users'] = $this->scanTextField(\App\Models\User::class, 'profile_photo_path', $variantSet, $fileMetadata);
                $totalFound += $usage['users']->count();
            }

            if (! isset($usage['dutiables'])) {
                $usage['dutiables'] = $this->scanTextField(\App\Models\Pivots\Dutiable::class, 'additional_photo', $variantSet, $fileMetadata);
                $totalFound += $usage['dutiables']->count();
            }
        }

        // Fill in empty collections for models that weren't scanned
        $allModelTypes = ['contentParts', 'calendar', 'news', 'banners', 'duties', 'institutions', 'trainings', 'types', 'forms', 'users', 'dutiables'];
        foreach ($allModelTypes as $modelType) {
            if (! isset($usage[$modelType])) {
                $usage[$modelType] = new Collection;
            }
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
     * Optimize variants for database queries (sort by efficiency)
     */
    private function optimizeVariantsForQuery(array $variants): array
    {
        // Sort variants by query efficiency:
        // 1. Longer, more specific patterns first
        // 2. Path-based patterns before filename-only
        // 3. Non-escaped before escaped patterns

        usort($variants, function ($a, $b) {
            // Prefer non-escaped patterns (more efficient queries)
            $aEscaped = str_contains($a, '\\');
            $bEscaped = str_contains($b, '\\');
            if ($aEscaped !== $bEscaped) {
                return $aEscaped ? 1 : -1;
            }

            // Prefer path-based patterns
            $aHasPath = str_contains($a, '/');
            $bHasPath = str_contains($b, '/');
            if ($aHasPath !== $bHasPath) {
                return $bHasPath ? 1 : -1;
            }

            // Prefer longer patterns (more specific)
            return strlen($b) - strlen($a);
        });

        return $variants;
    }

    /**
     * Try exact matches first (most efficient)
     */
    private function tryExactMatches(string $modelClass, string $field, array $variants): Collection
    {
        // For simple image URL fields, try exact matches
        $exactVariants = array_filter($variants, function ($variant) {
            // Skip variants with wildcards or complex patterns
            return ! str_contains($variant, '%') && ! str_contains($variant, '\\') && strlen($variant) > 3;
        });

        if (empty($exactVariants)) {
            return new Collection;
        }

        return $modelClass::whereIn($field, $exactVariants)->get();
    }

    /**
     * Execute optimized LIKE queries with early termination
     */
    private function executeLikeQuery(string $modelClass, string $field, array $variants): Collection
    {
        $results = new Collection;
        $maxVariantsToTry = 5; // Limit variants to avoid slow queries

        foreach (array_slice($variants, 0, $maxVariantsToTry) as $variant) {
            $query = $modelClass::where($field, 'LIKE', '%'.$variant.'%');

            // Add limit to prevent huge result sets
            $partialResults = $query->limit(10)->get();

            if ($partialResults->isNotEmpty()) {
                $results = $results->merge($partialResults);

                // Early termination if we found matches with specific patterns
                if (! str_contains($variant, '\\') && strlen($variant) > 10) {
                    break;
                }
            }
        }

        return $results->unique('id');
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
        $push = function ($value) use (&$variants) {
            if ($value !== null && $value !== '' && ! in_array($value, $variants, true)) {
                $variants[] = $value;
            }
        };

        // Priority 1: Most common and likely patterns first
        $this->addStructuralVariants($push, $originalPath, $normalizedUrl);

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
        $specificVariants = array_filter($allVariants, function ($variant) {
            return str_contains($variant, '/') && ! str_contains($variant, '\\');
        });
        if (! empty($specificVariants)) {
            $progressive[] = $specificVariants;
        }

        // Second pass: Filename patterns with escaping
        $escapedVariants = array_filter($allVariants, function ($variant) {
            return str_contains($variant, '\\');
        });
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
            $scannedTime = \Carbon\Carbon::parse($cachedScannedAt);
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

        // 2. JSON slash escaping for all current variants
        $currentVariants = $baseVariants; // Take snapshot to avoid infinite loop
        foreach ($currentVariants as $v) {
            $escaped = str_replace('/', '\\/', $v);
            if ($escaped !== $v) {
                $push($escaped);
            }
        }

        // 3. Unicode codepoint escapes (only if needed for complex Unicode)
        $this->addUnicodeCodepointEscapes($push, $currentVariants);
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
     * Scan ContentPart models for file references with optimized JSON queries
     */
    private function scanContentParts(string|array $urlOrVariants, array $fileMetadata = []): Collection
    {
        try {
            $variants = is_array($urlOrVariants) ? $urlOrVariants : [$urlOrVariants];
            $optimizedVariants = $this->optimizeVariantsForQuery($variants);

            // Phase 1: Try efficient JSON path queries first (MySQL 5.7+)
            $results = $this->tryJsonPathQueries($optimizedVariants);
            if ($results->isNotEmpty()) {
                return $results;
            }

            // Phase 2: Optimized LIKE queries with limits
            $results = $this->tryOptimizedContentPartLike($optimizedVariants);
            if ($results->isNotEmpty()) {
                return $results;
            }

            // Phase 3: Selective fallback for complex Unicode (only if needed)
            return $this->trySelectiveUnicodeFallback($variants, $optimizedVariants);

        } catch (\Exception $e) {
            Log::error('Error scanning ContentParts', [
                'variants' => $urlOrVariants,
                'error' => $e->getMessage(),
            ]);

            return new Collection;
        }
    }

    /**
     * Try JSON path queries for better performance on JSON columns
     */
    private function tryJsonPathQueries(array $variants): Collection
    {
        $results = new Collection;

        // Try most specific variants first
        $specificVariants = array_slice($variants, 0, 3);

        foreach ($specificVariants as $variant) {
            // Skip very short or complex patterns
            if (strlen($variant) < 5 || str_contains($variant, '\\')) {
                continue;
            }

            // Use JSON_SEARCH for better performance on JSON columns
            $query = ContentPart::query()
                ->whereIn('type', ['tiptap', 'shadcn-card', 'shadcn-accordion', 'hero'])
                ->whereRaw("JSON_SEARCH(json_content, 'one', ?) IS NOT NULL", [$variant])
                ->limit(10); // Prevent huge result sets

            $partialResults = $query->with('content')->get();

            if ($partialResults->isNotEmpty()) {
                $results = $results->merge($partialResults);

                // Early termination for specific patterns
                if (str_contains($variant, '/') && strlen($variant) > 15) {
                    break;
                }
            }
        }

        return $results->unique('id');
    }

    /**
     * Try optimized LIKE queries with chunking
     */
    private function tryOptimizedContentPartLike(array $variants): Collection
    {
        $results = new Collection;
        $maxVariants = 3; // Limit to most promising variants

        foreach (array_slice($variants, 0, $maxVariants) as $variant) {
            if (strlen($variant) < 4) {
                continue;
            } // Skip very short patterns

            $query = ContentPart::query()
                ->whereIn('type', ['tiptap', 'shadcn-card', 'shadcn-accordion', 'hero'])
                ->where('json_content', 'LIKE', '%'.$variant.'%')
                ->limit(5); // Aggressive limit for LIKE queries

            $partialResults = $query->with('content')->get();

            if ($partialResults->isNotEmpty()) {
                $results = $results->merge($partialResults);

                // Early termination if we found good matches
                if (! str_contains($variant, '\\') && strlen($variant) > 10) {
                    break;
                }
            }
        }

        return $results->unique('id');
    }

    /**
     * Selective fallback for complex Unicode patterns (most expensive)
     */
    private function trySelectiveUnicodeFallback(array $originalVariants, array $optimizedVariants): Collection
    {
        // Only run expensive fallback if we have Unicode escape sequences
        $needsEscapedCheck = collect($originalVariants)->contains(function ($v) {
            return str_contains($v, '\\u') || str_contains($v, '\\/');
        });

        if (! $needsEscapedCheck) {
            return new Collection;
        }

        // Get a small sample of ContentParts to test
        $sampleParts = ContentPart::query()
            ->whereIn('type', ['tiptap', 'shadcn-card', 'shadcn-accordion', 'hero'])
            ->limit(50) // Much smaller sample
            ->get();

        $matched = $sampleParts->filter(function ($part) use ($optimizedVariants) {
            $content = json_encode($part->json_content);
            if ($content === false) {
                return false;
            }

            // Only check top 2 most promising variants
            foreach (array_slice($optimizedVariants, 0, 2) as $v) {
                $withEscapedSlashes = str_replace('/', '\\/', $v);
                if (strpos($content, $v) !== false || strpos($content, $withEscapedSlashes) !== false) {
                    return true;
                }
            }

            return false;
        });

        if ($matched->isNotEmpty()) {
            $matched->load('content');

            return $matched->values();
        }

        return new Collection;
    }

    /**
     * Scan translatable fields (JSON columns) with optimized query patterns
     */
    private function scanTranslatableField(string $modelClass, string $field, string|array $urlOrVariants, array $fileMetadata = []): Collection
    {
        try {
            $variants = is_array($urlOrVariants) ? $urlOrVariants : [$urlOrVariants];

            // Sort variants by likelihood of match (exact matches first)
            $optimizedVariants = $this->optimizeVariantsForQuery($variants);

            // Try exact matches first (more efficient than LIKE)
            $exactMatches = $this->tryExactMatches($modelClass, $field, $optimizedVariants);
            if ($exactMatches->isNotEmpty()) {
                return $exactMatches;
            }

            // Fall back to optimized LIKE queries
            return $this->executeLikeQuery($modelClass, $field, $optimizedVariants);

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

            // Sort variants by likelihood of match (exact matches first)
            $optimizedVariants = $this->optimizeVariantsForQuery($variants);

            // Try exact matches first (more efficient than LIKE)
            $exactMatches = $this->tryExactMatches($modelClass, $field, $optimizedVariants);
            if ($exactMatches->isNotEmpty()) {
                return $exactMatches;
            }

            // Fall back to optimized LIKE queries
            return $this->executeLikeQuery($modelClass, $field, $optimizedVariants);

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
                        'model_class' => $owner ? get_class($owner) : 'App\\Models\\Content',
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
                    'model_class' => get_class($result),
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
     */
    private function resolvePrimaryOwnerForContent(int $contentId): ?object
    {
        static $cache = [];
        if (array_key_exists($contentId, $cache)) {
            return $cache[$contentId];
        }

        $owner = \App\Models\Page::where('content_id', $contentId)->first()
            ?? \App\Models\News::where('content_id', $contentId)->first()
            ?? \App\Models\Tenant::where('content_id', $contentId)->first();

        $cache[$contentId] = $owner;

        return $owner;
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
                'training' => 'trainings',
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
        } catch (\Exception $e) {
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
                'training' => 'trainings',
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
        } catch (\Exception $e) {
            return null;
        }
    }
}
