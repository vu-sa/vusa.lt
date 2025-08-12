<?php

namespace App\Services;

use App\Models\Calendar;
use App\Models\ChangelogItem;
use App\Models\ContentPart;
use App\Models\Dutiable;
use App\Models\Duty;
use App\Models\Form;
use App\Models\Institution;
use App\Models\News;
use App\Models\Training;
use App\Models\Type;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class FileUsageScanner
{
    /**
     * Scan for file usage across all TipTap-enabled models
     */
    public function scanFileUsage(string $filePath): array
    {
        // Build list of possible URL/path variants for robust searching (legacy + current)
        $normalizedUrl = $this->normalizeFileUrl($filePath);
        $variants = $this->buildSearchVariants($filePath, $normalizedUrl);
    // Debug dd removed; variants now include escaped Unicode forms
        
        // Disable cache for debugging
        $usage = [
            // Rich Content System (ContentPart)
            'contentParts' => $this->scanContentParts($variants),
            
            // Direct TipTap fields (confirmed usage from analysis)
            'calendar' => $this->scanTranslatableField(Calendar::class, 'description', $variants),
            'news' => $this->scanTextField(News::class, 'short', $variants),
            'duties' => $this->scanTranslatableField(Duty::class, 'description', $variants),
            'institutions' => $this->scanTranslatableField(Institution::class, 'description', $variants),
            'trainings' => $this->scanTranslatableField(Training::class, 'description', $variants),
            'types' => $this->scanTranslatableField(Type::class, 'description', $variants),
            'forms' => $this->scanTranslatableField(Form::class, 'description', $variants),
            'changelogItems' => $this->scanTranslatableField(ChangelogItem::class, 'description', $variants),
        ];
        
    return $this->processUsageResults($usage, $normalizedUrl);
    }

    /**
     * Build a list of possible stored path/url variants (legacy + current) to increase hit rate.
     */
    private function buildSearchVariants(string $originalPath, string $normalizedUrl): array
    {
    /**
     * Generate plausible serialized representations of a file path as stored in rich text JSON:
     * - Structural variants (legacy /uploads/, storage symlink, raw public path, filename-only)
     * - Unicode normalization (NFC + NFD) for combining mark differences
     * - JSON slash escaping (\/)
     * Full \uXXXX codepoint expansion removed as NFD + slash escaping covers observed cases.
     */
        /**
         * Variant strategy tiers:
         * 1. Structural path forms (canonical, no leading slash, legacy, storage, raw public, filename)
         * 2. Unicode normalization (NFC/NFD) only if non-ASCII present
         * 3. JSON slash escaped (\/)
         * 4. Unicode codepoint escapes (\uXXXX) only if non-ASCII present (covers decomposed combining marks)
         */

        $variants = [];
        $push = function($value) use (&$variants) {
            if ($value !== null && $value !== '' && !in_array($value, $variants, true)) {
                $variants[] = $value;
            }
        };

        // 1. Structural
        $push($normalizedUrl);
        $push(ltrim($normalizedUrl, '/'));

        if (str_contains($normalizedUrl, '/uploads/files/')) {
            $legacy = str_replace('/uploads/files/', '/uploads/', $normalizedUrl);
            $push($legacy);
            $push(ltrim($legacy, '/'));
        }

        if (str_starts_with($normalizedUrl, '/uploads/files/')) {
            $storageVariant = str_replace('/uploads/files/', '/storage/files/', $normalizedUrl);
            $push($storageVariant);
            $push(ltrim($storageVariant, '/'));
        }

        if (str_starts_with($originalPath, 'public/')) {
            $push('/' . str_replace('public/', 'uploads/', $originalPath));
            $push($originalPath);
        }

        $filename = basename($normalizedUrl);
        $push($filename);

        $hasNonAscii = (bool) preg_match('/[^\x00-\x7F]/', $normalizedUrl);

        // 2. Unicode normalization (only if needed)
        if ($hasNonAscii && class_exists(\Normalizer::class)) {
            foreach ([\Normalizer::FORM_C, \Normalizer::FORM_D] as $form) {
                $norm = \Normalizer::normalize($normalizedUrl, $form);
                if ($norm) {
                    $push($norm);
                    $push(ltrim($norm, '/'));
                    $push(basename($norm));
                }
            }
        }

        // 3. JSON slash escaped
        $jsonEscaped = [];
        foreach ($variants as $v) {
            $e = str_replace('/', '\\/', $v);
            if ($e !== $v) $jsonEscaped[] = $e;
        }
        foreach ($jsonEscaped as $e) $push($e);

        // 4. Targeted unicode escapes: only escape combining marks (U+0300–U+036F) & leave base chars intact.
        if ($hasNonAscii) {
            $combiningEscaped = [];
            foreach ($variants as $v) {
                // Only process strings that actually contain combining marks
                if (!preg_match('/\p{M}/u', $v)) continue;
                $escaped = preg_replace_callback('/(\p{M})/u', function($m) {
                    $cp = strtoupper(dechex(mb_ord($m[0], 'UTF-8')));
                    return '\\u' . str_pad($cp, 4, '0', STR_PAD_LEFT);
                }, $v);
                if ($escaped && $escaped !== $v) {
                    $combiningEscaped[] = $escaped;
                    // Also add slash-escaped version of this combining-escaped variant
                    $slashEsc = str_replace('/', '\\/', $escaped);
                    if ($slashEsc !== $escaped) $combiningEscaped[] = $slashEsc;
                }
            }
            foreach (array_unique($combiningEscaped) as $cv) $push($cv);

            // Full codepoint escape for entire string (covers precomposed letters stored as \u0161, etc.)
            if (function_exists('mb_ord')) {
                $fullEscapedSet = [];
                foreach ($variants as $v) {
                    if (!preg_match('/[^\x00-\x7F]/', $v)) continue; // ASCII only skip
                    $fullEscaped = preg_replace_callback('/[\x{80}-\x{10FFFF}]/u', function($m) {
                        $cp = strtolower(dechex(mb_ord($m[0], 'UTF-8')));
                        return '\\u' . str_pad($cp, 4, '0', STR_PAD_LEFT);
                    }, $v);
                    if ($fullEscaped && $fullEscaped !== $v) {
                        $fullEscapedSet[] = $fullEscaped;
                        $slashEsc = str_replace('/', '\\/', $fullEscaped);
                        if ($slashEsc !== $fullEscaped) $fullEscapedSet[] = $slashEsc;
                    }
                }
                foreach (array_unique($fullEscapedSet) as $fe) $push($fe);
            }
        }

        return $variants; // Already unique by $push logic
    }
    
    private function getModelClass(string $modelType): ?string
    {
        $mapping = [
            'calendar' => Calendar::class,
            'news' => News::class,
            'duties' => Duty::class,
            'institutions' => Institution::class,
            'trainings' => Training::class,
            'types' => Type::class,
            'forms' => Form::class,
            'changelogItems' => ChangelogItem::class,
        ];
        
        return $mapping[$modelType] ?? null;
    }
    
    private function getFieldForModel(string $modelType): string
    {
        $mapping = [
            'calendar' => 'description',
            'news' => 'short',
            'duties' => 'description',
            'institutions' => 'description',
            'trainings' => 'description',
            'types' => 'description',
            'forms' => 'description',
            'changelogItems' => 'description',
        ];
        
        return $mapping[$modelType] ?? 'description';
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
     * Find all unused files in a directory older than specified days
     */
    public function findUnusedFiles(string $directoryPath, int $olderThanDays = 30): array
    {
        // This would be implemented to scan filesystem and check usage
        // For now, return structure for the frontend to use
        return [
            'unusedFiles' => [],
            'checkedFiles' => [],
            'errors' => []
        ];
    }
    
    /**
     * Normalize file path to URL format for searching
     */
    private function normalizeFileUrl(string $filePath): string
    {
        // Convert from "public/files/..." to "/uploads/files/..."
        if (str_starts_with($filePath, 'public/')) {
            return '/' . str_replace('public/', 'uploads/', $filePath);
        }
        
        // If already in URL format, return as is
        if (str_starts_with($filePath, '/uploads/')) {
            return $filePath;
        }
        
        // Default case - assume it's a relative path from uploads
        return '/uploads/' . ltrim($filePath, '/');
    }
    
    /**
     * Scan ContentPart models for file references
     */
    private function scanContentParts(string|array $urlOrVariants): Collection
    {
        try {
            $query = ContentPart::query()
                ->whereIn('type', ['tiptap', 'shadcn-card', 'shadcn-accordion', 'hero']);

            $variants = is_array($urlOrVariants) ? $urlOrVariants : [$urlOrVariants];

            $query->where(function ($q) use ($variants) {
                foreach ($variants as $v) {
                    $q->orWhere('json_content', 'LIKE', '%' . $v . '%');
                }
            });

            $results = $query->with('content')->get();

            // If still no results, try a more permissive fallback: search by filename alone again (in case of deep nesting / HTML stripping)
            if ($results->isEmpty()) {
                if (collect($variants)->contains(fn($v)=>preg_match('/[^\x00-\x7F]/', $v))) {
                    Log::debug('FileUsageScanner: No direct SQL matches for non-ASCII variants', [
                        'variants' => $variants
                    ]);
                }
                $filenameOnly = basename($variants[0]);
                if ($filenameOnly) {
                    $fallback = ContentPart::query()
                        ->whereIn('type', ['tiptap', 'shadcn-card', 'shadcn-accordion', 'hero'])
                        ->where('json_content', 'LIKE', '%' . $filenameOnly . '%')
                        ->with('content')
                        ->get();
                    if ($fallback->isNotEmpty()) {
                        return $fallback;
                    }
                }
            }

            // Conditional fallback: only run heavy PHP-level scan if variants hint at escaped sequences
            // (\uXXXX or \/). This protects performance for ordinary ASCII paths.
            $needsEscapedCheck = collect($variants)->contains(function($v){
                return str_contains($v, '\\u') || str_contains($v, '\\/');
            });
            if ($results->isEmpty() && $needsEscapedCheck) {
                $allParts = ContentPart::query()
                    ->whereIn('type', ['tiptap', 'shadcn-card', 'shadcn-accordion', 'hero'])
                    ->get();

                $matched = $allParts->filter(function($part) use ($variants) {
                    $content = $part->json_content;
                    if (!is_string($content)) {
                        $content = json_encode($content);
                    }
                    if ($content === null) return false;
                    foreach ($variants as $v) {
                        // Also consider variant with added backslashes before slashes
                        $withEscapedSlashes = str_replace('/', '\\/', $v);
                        if (strpos($content, $v) !== false || strpos($content, $withEscapedSlashes) !== false) {
                            return true;
                        }
                    }
                    return false;
                });

                if ($matched->isNotEmpty()) {
                    // Eager load content for matched subset
                    $matched->load('content');
                    return $matched->values();
                }
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('Error scanning ContentParts', [
                'variants' => $urlOrVariants,
                'error' => $e->getMessage()
            ]);
            return new Collection();
        }
    }
    
    /**
     * Scan translatable fields (JSON columns)
     */
    private function scanTranslatableField(string $modelClass, string $field, string|array $urlOrVariants): Collection
    {
        try {
            $variants = is_array($urlOrVariants) ? $urlOrVariants : [$urlOrVariants];
            return $modelClass::where(function ($q) use ($field, $variants) {
                foreach ($variants as $v) {
                    $q->orWhere($field, 'LIKE', '%' . $v . '%');
                }
            })->get();
        } catch (\Exception $e) {
            Log::error('Error scanning translatable field', [
                'model' => $modelClass,
                'field' => $field,
                'variants' => $urlOrVariants,
                'error' => $e->getMessage()
            ]);
            return new Collection();
        }
    }
    
    /**
     * Scan regular text fields
     */
    private function scanTextField(string $modelClass, string $field, string|array $urlOrVariants): Collection
    {
        try {
            $variants = is_array($urlOrVariants) ? $urlOrVariants : [$urlOrVariants];
            return $modelClass::where(function ($q) use ($field, $variants) {
                foreach ($variants as $v) {
                    $q->orWhere($field, 'LIKE', '%' . $v . '%');
                }
            })->get();
        } catch (\Exception $e) {
            Log::error('Error scanning text field', [
                'model' => $modelClass,
                'field' => $field,
                'variants' => $urlOrVariants,
                'error' => $e->getMessage()
            ]);
            return new Collection();
        }
    }
    
    /**
     * Process usage results into a structured format
     */
    private function processUsageResults(array $usage, string $url): array
    {
        $usageDetails = [];
        $totalUsages = 0;
        $rawPartMatches = [];

    // Gather file metadata for robustness (existence + size + hashes)
    $publicPath = $this->convertUrlToPublicPath($url); // e.g. /uploads/files/... -> public/files/...
    $absoluteStoragePath = storage_path('app/' . $publicPath); // Storage::put uses storage/app/public/...
    $fileExists = file_exists($absoluteStoragePath);
    $filesize = $fileExists ? @filesize($absoluteStoragePath) : null;
    $hashSha256 = $fileExists ? @hash_file('sha256', $absoluteStoragePath) : null;
    $hashMd5 = $fileExists ? @hash_file('md5', $absoluteStoragePath) : null;

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
            return 'public/' . ltrim(str_replace('/uploads/', '', $url), '/');
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
                
                if (is_string($value) && !empty(trim($value))) {
                    return trim(strip_tags($value));
                }
            }
        }
        
        // Fallback to model class and ID
        return class_basename($model) . ' #' . $model->id;
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
                'changelogitem' => 'changelogItems',
                'dutiable' => 'dutiables',
                'page' => 'pages',
                'tenant' => 'tenants',
                'contentpart' => null, // ContentParts don't have direct admin URLs
            ];
            
            $routeName = $routeNames[$modelName] ?? null;
            
            if ($routeName && $model->id) {
                return route($routeName . '.show', $model->id);
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
                'changelogitem' => 'changelogItems',
                'dutiable' => 'dutiables',
                'page' => 'pages',
                'tenant' => 'tenants',
            ];

            $base = $routeNames[$modelName] ?? null;
            if ($base && $model->id && Route::has($base . '.edit')) {
                return route($base . '.edit', $model->id);
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
}