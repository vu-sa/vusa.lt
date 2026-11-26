<?php

namespace App\Services\Typesense;

use App\Models\Calendar;
use App\Models\Content;
use App\Models\Duty;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Services\ContentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SearchText
{
    public static function plain(?string $value): string
    {
        return Str::squish(strip_tags(preg_replace('/<[^>]+>/', ' ', html_entity_decode($value ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? ''));
    }

    /** @return array<string, string> */
    public static function forModel(Model $model): array
    {
        $result = [];
        if ($model instanceof News || $model instanceof Page) {
            $model->loadMissing('content.parts', 'tags');
            $content = $model->getRelation('content');
            $result['body'] = self::plain($content instanceof Content ? app(ContentService::class)->generateSearchableContent($content) : '');
        }

        $fields = self::fieldsFor($model);

        foreach (['lt', 'en'] as $locale) {
            $parts = [
                ...self::fieldValuesForLocale($model, $fields, $locale),
                ...self::relationPartsForLocale($model, $locale, $result['body'] ?? ''),
            ];
            $result['search_text_'.$locale] = self::plain(implode(' ', $parts));
        }

        return $result;
    }

    /** @return list<string> */
    private static function fieldsFor(Model $model): array
    {
        return match (class_basename($model)) {
            'News', 'PublicNews' => ['title', 'short'],
            'Page', 'PublicPage' => ['title', 'meta_description'],
            'Calendar', 'Resource' => ['title', 'name', 'description'],
            'Meeting', 'PublicMeeting' => ['description'],
            'AgendaItem' => ['title', 'description', 'student_position', 'student_benefit'],
            'Document' => ['title', 'summary', 'content_type'],
            'Duty' => ['name', 'description'],
            'Institution', 'PublicInstitution' => ['name', 'short_name', 'description'],
            default => [],
        };
    }

    /**
     * @param  list<string>  $fields
     * @return list<string>
     */
    private static function fieldValuesForLocale(Model $model, array $fields, string $locale): array
    {
        $parts = [];
        $attributes = $model->getAttributes();
        $modelLocale = $attributes['lang'] ?? self::documentLocale($model);

        foreach ($fields as $field) {
            if (! array_key_exists($field, $attributes)) {
                continue;
            }
            if (method_exists($model, 'isTranslatableAttribute') && method_exists($model, 'getTranslations') && $model->isTranslatableAttribute($field)) {
                $parts[] = $model->getTranslations($field)[$locale] ?? '';
            } elseif ($modelLocale === $locale) {
                $value = $model->getAttribute($field);
                if (is_string($value)) {
                    $parts[] = $value;
                }
            }
        }

        return $parts;
    }

    /** @return list<string> */
    private static function relationPartsForLocale(Model $model, string $locale, string $body): array
    {
        if ($model instanceof News || $model instanceof Page) {
            return $model->lang === $locale ? [$body, ...self::tagNamesForLocale($model, $locale)] : [];
        }
        if ($model instanceof Calendar) {
            $model->loadMissing('tags');

            return self::tagNamesForLocale($model, $locale);
        }
        if ($model instanceof Duty) {
            $model->loadMissing('institution.tenant');

            return [
                $model->institution?->getTranslations('name')[$locale] ?? '',
                $model->institution?->tenant->shortname ?? '',
            ];
        }
        if ($model instanceof Meeting) {
            $model->loadMissing('institutions');
            $parts = $locale === 'lt' && is_string($model->title) ? [$model->title] : [];
            foreach ($model->institutions as $institution) {
                $parts[] = $institution->getTranslations('name')[$locale] ?? '';
            }

            return $parts;
        }

        return [];
    }

    /** @return list<string> */
    private static function tagNamesForLocale(News|Page|Calendar $model, string $locale): array
    {
        $names = [];
        foreach ($model->tags as $tag) {
            $names[] = $tag->getTranslations('name')[$locale] ?? '';
        }

        return $names;
    }

    private static function documentLocale(Model $model): ?string
    {
        return match (strtolower((string) ($model->getAttributes()['language'] ?? ''))) {
            'lt', 'lit', 'lithuanian', 'lietuvių', 'lietuvių kalba' => 'lt',
            'en', 'eng', 'english', 'anglų', 'anglų kalba' => 'en',
            default => null,
        };
    }
}
