<?php

namespace App\Services;

use App\Models\Banner;
use App\Models\Calendar;
use App\Models\ContentEditorDraft;
use App\Models\ContentPart;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Goal;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Navigation;
use App\Models\News;
use App\Models\Page;
use App\Models\Pivots\Dutiable;
use App\Models\Problem;
use App\Models\QuickLink;
use App\Models\Step;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use App\Services\FileUsage\FileReferenceMatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/**
 * Finds every record that references a File Manager file, so the admin can
 * tell whether deleting it would break a page, image or link.
 *
 * Two stages: an SQL pre-filter on substrings every encoding of the path keeps
 * (FileReferenceMatcher::coarseNeedles()), then an exact check of each
 * candidate value in PHP (FileReferenceMatcher::matches()).
 */
class FileUsageScanner
{
    /**
     * Admin page per model. Content opens in its editor, work objects on their record page.
     *
     * @var array<class-string<Model>, string>
     */
    private const array ADMIN_ROUTES = [
        Page::class => 'pages.edit',
        News::class => 'news.edit',
        Banner::class => 'banners.edit',
        Calendar::class => 'calendar.edit',
        Tag::class => 'tags.edit',
        QuickLink::class => 'quickLinks.edit',
        Navigation::class => 'navigation.edit',
        Institution::class => 'institutions.show',
        Duty::class => 'duties.show',
        InstitutionType::class => 'institutionTypes.show',
        DutyType::class => 'dutyTypes.show',
        Form::class => 'forms.show',
        Problem::class => 'problems.show',
        Goal::class => 'goals.show',
        User::class => 'users.show',
        Tenant::class => 'tenants.show',
    ];

    /**
     * Every column that can hold an upload URL — plain, HTML or JSON alike.
     *
     * @return array<string, array{class-string<Model>, list<string>}>
     */
    public static function targets(): array
    {
        return [
            'contentParts' => [ContentPart::class, ['json_content', 'options']],
            'news' => [News::class, ['short', 'image']],
            'pages' => [Page::class, ['featured_image']],
            'banners' => [Banner::class, ['image_url', 'link_url']],
            'institutions' => [Institution::class, ['description', 'image_url', 'logo_url']],
            'calendar' => [Calendar::class, ['description', 'cto_url']],
            'duties' => [Duty::class, ['description']],
            'dutiables' => [Dutiable::class, ['description', 'additional_photo']],
            'institutionTypes' => [InstitutionType::class, ['description']],
            'dutyTypes' => [DutyType::class, ['description']],
            'forms' => [Form::class, ['description']],
            'formFields' => [FormField::class, ['description']],
            'problems' => [Problem::class, ['description', 'solution', 'steps_taken']],
            'goals' => [Goal::class, ['description', 'evaluation']],
            'steps' => [Step::class, ['description', 'url']],
            'tags' => [Tag::class, ['description']],
            'navigation' => [Navigation::class, ['url', 'extra_attributes']],
            'quickLinks' => [QuickLink::class, ['link']],
            'users' => [User::class, ['profile_photo_path']],
            'contentEditorDrafts' => [ContentEditorDraft::class, ['snapshot']],
        ];
    }

    /**
     * @param  string  $filePath  a File Manager path: `public/files/…`, `uploads/files/…` or relative to `files/`
     * @return array{file_url: string, total_usages: int, is_safe_to_delete: bool, file_exists: bool, usage_details: list<array<string, mixed>>, scanned_models: list<string>, scanned_at: string}
     */
    public function scanFileUsage(string $filePath): array
    {
        $relativePath = $this->relativePath($filePath);

        // `/uploads/<path>` is redirected to `/uploads/files/<path>` (RewriteUploadsUrl)
        // only when no file sits at `public/<path>` — otherwise it is that other file.
        $matcher = new FileReferenceMatcher($relativePath, ! Storage::exists('public/'.$relativePath));

        $usageDetails = [];

        foreach (self::targets() as $key => [$modelClass, $columns]) {
            $ids = $this->matchingIds($modelClass, $columns, $matcher);

            if ($ids === []) {
                continue;
            }

            $details = $key === 'contentParts'
                ? $this->contentPartDetails($ids)
                : self::queryIncludingTrashed($modelClass)->whereKey($ids)->get()
                    ->map(fn (Model $model): array => $this->detail($key, $model))
                    ->all();

            array_push($usageDetails, ...$details);
        }

        return [
            'file_url' => '/uploads/files/'.$relativePath,
            'total_usages' => count($usageDetails),
            'is_safe_to_delete' => $usageDetails === [],
            'file_exists' => Storage::exists('public/files/'.$relativePath),
            'usage_details' => $usageDetails,
            'scanned_models' => array_keys(self::targets()),
            'scanned_at' => now()->toISOString(),
        ];
    }

    private function relativePath(string $filePath): string
    {
        $path = ltrim($filePath, '/');

        foreach (['public/files/', 'uploads/files/', 'files/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return substr($path, strlen($prefix));
            }
        }

        return $path;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  list<string>  $columns
     * @return list<int|string>
     */
    private function matchingIds(string $modelClass, array $columns, FileReferenceMatcher $matcher): array
    {
        $keyName = (new $modelClass)->getKeyName();
        $needles = $matcher->coarseNeedles();

        $query = self::queryIncludingTrashed($modelClass)->toBase()
            ->select([$keyName, ...$columns])
            ->where(function (QueryBuilder $query) use ($columns, $needles): void {
                foreach ($columns as $column) {
                    $query->orWhere(function (QueryBuilder $query) use ($column, $needles): void {
                        $query->whereNotNull($column);

                        foreach ($needles as $needle) {
                            $query->whereRaw(
                                $query->getGrammar()->wrap($column)." LIKE ? ESCAPE '|'",
                                ['%'.$this->escapeLike($needle).'%'],
                            );
                        }
                    });
                }
            });

        $ids = [];

        foreach ($query->cursor() as $row) {
            foreach ($columns as $column) {
                if ($matcher->matches($row->{$column})) {
                    $ids[] = $row->{$keyName};

                    break;
                }
            }
        }

        return $ids;
    }

    /**
     * Content blocks are reported once per owning page, article or tenant homepage.
     *
     * @param  list<int|string>  $ids
     * @return list<array<string, mixed>>
     */
    private function contentPartDetails(array $ids): array
    {
        return ContentPart::query()
            ->whereKey($ids)
            ->with(['content.news', 'content.page', 'content.tenantHomepageContent.tenant'])
            ->get()
            ->groupBy('content_id')
            ->map(function ($parts, $contentId): array {
                $owner = $parts->first()->content->owner();

                $detail = $owner !== null
                    ? $this->detail(strtolower(class_basename($owner)), $owner)
                    : [
                        'model_type' => 'content',
                        'model_class' => ContentPart::class,
                        'id' => $contentId,
                        'title' => 'Content #'.$contentId,
                        'url' => null,
                        'created_at' => null,
                        'updated_at' => null,
                    ];

                return [...$detail, 'matched_parts_count' => $parts->count()];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(string $modelType, Model $model): array
    {
        return [
            'model_type' => $modelType,
            'model_class' => $model::class,
            'id' => $model->getKey(),
            'title' => $this->title($model),
            'url' => $this->adminUrl($model),
            'created_at' => $model->getAttribute('created_at'),
            'updated_at' => $model->getAttribute('updated_at'),
        ];
    }

    private function title(Model $model): string
    {
        foreach (['title', 'name', 'fullname', 'subject'] as $field) {
            $value = $model->getAttribute($field);

            if (is_array($value)) {
                $value = $value['lt'] ?? $value['en'] ?? null;
            }

            if (is_string($value) && trim(strip_tags($value)) !== '') {
                return trim(strip_tags($value));
            }
        }

        return class_basename($model).' #'.$model->getKey();
    }

    private function adminUrl(Model $model): ?string
    {
        [$routeName, $parameter] = match (true) {
            $model instanceof FormField => ['forms.show', $model->form_id],
            $model instanceof Step => ['goals.show', $model->goal_id],
            $model instanceof Dutiable => ['duties.show', $model->duty_id],
            default => [self::ADMIN_ROUTES[$model::class] ?? null, $model->getKey()],
        };

        if ($routeName === null || $parameter === null || ! Route::has($routeName)) {
            return null;
        }

        return route($routeName, $parameter);
    }

    /**
     * Trashed rows count: a file used only by a deleted banner or article is
     * not unused, and deleting it would break the record once it is restored.
     *
     * @param  class-string<Model>  $modelClass
     * @return Builder<Model>
     */
    private static function queryIncludingTrashed(string $modelClass): Builder
    {
        $query = $modelClass::query();

        return in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)
            ? $query->withoutGlobalScope(SoftDeletingScope::class)
            : $query;
    }

    /**
     * Escape a LIKE pattern with '|' as the escape character, keeping '%', '_'
     * and '\' literal on both MySQL (default escape '\') and SQLite (none).
     */
    private function escapeLike(string $needle): string
    {
        return str_replace(['|', '%', '_'], ['||', '|%', '|_'], $needle);
    }
}
