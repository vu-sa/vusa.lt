<?php

namespace App\Services\Typesense;

use App\Models\Calendar;
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
            $result['body'] = self::plain($model->content ? app(ContentService::class)->generateSearchableContent($model->content) : '');
        }

        $fields = match (class_basename($model)) {
            'News', 'PublicNews' => ['title', 'short'],
            'Page', 'PublicPage' => ['title', 'meta_description'],
            'Calendar', 'Resource' => ['title', 'name', 'description'],
            'Meeting', 'PublicMeeting' => ['description'],
            'AgendaItem' => ['title', 'description', 'student_position', 'student_benefit'],
            'Document' => ['title', 'summary', 'content_type'],
            'Duty' => ['name', 'description'],
            'Institution', 'PublicInstitution' => ['description'],
            default => [],
        };

        foreach (['lt', 'en'] as $locale) {
            $parts = [];
            foreach ($fields as $field) {
                if (! array_key_exists($field, $model->getAttributes())) {
                    continue;
                }
                if (method_exists($model, 'isTranslatableAttribute') && $model->isTranslatableAttribute($field)) {
                    $parts[] = $model->getTranslations($field)[$locale] ?? '';
                } elseif (($model->getAttributes()['lang'] ?? self::documentLocale($model)) === $locale) {
                    $value = $model->getAttribute($field);
                    if (is_string($value)) {
                        $parts[] = $value;
                    }
                }
            }
            if (($model instanceof News || $model instanceof Page) && $model->lang === $locale) {
                $parts[] = $result['body'];
                foreach ($model->tags as $tag) {
                    $parts[] = $tag->getTranslations('name')[$locale] ?? '';
                }
            }
            if ($model instanceof Calendar) {
                $model->loadMissing('tags');
                foreach ($model->tags as $tag) {
                    $parts[] = $tag->getTranslations('name')[$locale] ?? '';
                }
            }
            $result['search_text_'.$locale] = self::plain(implode(' ', $parts));
        }

        return $result;
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
