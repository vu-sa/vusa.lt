<?php

/**
 * A translatable field whose name says it may hold markup is either sanitized on write
 * (`sanitizedHtmlTranslations()` / `inlineHtmlTranslations()`) or listed below as plain text.
 * A new Tiptap-authored field therefore cannot reach a `v-html` sink unsanitized by default.
 */

use App\Models\EventType;
use App\Models\Goal;
use App\Models\KnowledgeSection;
use App\Models\LearningAudience;
use App\Models\LecturerReview;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Pivots\Dutiable;
use App\Models\ProblemCategory;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\Role;
use App\Models\StudySet;
use App\Models\Traits\HasTranslations;
use App\Models\Type;
use App\Models\Vote;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

/** Name segments that suggest a field may hold markup. */
const RICH_FIELD_SEGMENTS = ['about', 'answer', 'bio', 'body', 'comment', 'content', 'description', 'details', 'evaluation', 'excerpt', 'html', 'instructions', 'intro', 'lead', 'message', 'note', 'notes', 'position', 'result', 'solution', 'steps', 'summary', 'text'];

/**
 * Fields no Tiptap editor writes and no `v-html` renders. Each names its editor.
 *
 * @var array<class-string, array<string, string>>
 */
const PLAIN_TEXT_TRANSLATIONS = [
    AgendaItem::class => ['description' => 'plain input', 'student_position' => 'plain input'],
    Dutiable::class => ['study_program_note' => 'plain input'],
    EventType::class => ['description' => 'MultiLocaleInput textarea'],
    Goal::class => ['expected_result' => 'plain input; description and evaluation are the Tiptap fields'],
    KnowledgeSection::class => ['description' => 'plain input'],
    LearningAudience::class => ['description' => 'Textarea in LearningAudienceSheet'],
    LecturerReview::class => ['comment' => 'plain input'],
    Meeting::class => ['description' => 'plain input'],
    ProblemCategory::class => ['description' => 'plain input'],
    Resource::class => ['description' => 'plain input in ResourceForm'],
    ResourceCategory::class => ['description' => 'plain input'],
    Role::class => ['description' => 'plain input'],
    StudySet::class => ['description' => 'Textarea in StudySetForm'],
    Type::class => ['description' => 'legacy type, no edit form'],
    Vote::class => ['note' => 'plain input'],
];

/**
 * @return list<class-string>
 */
function translatableModelClasses(): array
{
    return collect(File::allFiles(app_path('Models')))
        ->map(fn (SplFileInfo $file): string => 'App\\Models\\'.Str::of($file->getRelativePathname())->beforeLast('.php')->replace('/', '\\'))
        ->filter(fn (string $class): bool => class_exists($class) && in_array(HasTranslations::class, class_uses_recursive($class), true))
        ->values()
        ->all();
}

/** Public* mirrors inherit their parent's fields, so they share its exemptions. */
function isDeclaredPlainText(string $class, string $field): bool
{
    foreach ([$class, ...class_parents($class)] as $candidate) {
        if (isset(PLAIN_TEXT_TRANSLATIONS[$candidate][$field])) {
            return true;
        }
    }

    return false;
}

/**
 * @return list<string>
 */
function sanitizedTranslations(object $model): array
{
    return [
        ...(new ReflectionMethod($model, 'sanitizedHtmlTranslations'))->invoke($model),
        ...(new ReflectionMethod($model, 'inlineHtmlTranslations'))->invoke($model),
    ];
}

test('every rich-named translatable field is sanitized or declared plain text', function (): void {
    $unclassified = [];

    foreach (translatableModelClasses() as $class) {
        $model = new $class;
        $sanitized = sanitizedTranslations($model);

        foreach ($model->getTranslatableAttributes() as $field) {
            $looksRich = array_intersect(explode('_', $field), RICH_FIELD_SEGMENTS) !== [];

            if ($looksRich && ! in_array($field, $sanitized, true) && ! isDeclaredPlainText($class, $field)) {
                $unclassified[] = "{$class}::\${$field}";
            }
        }
    }

    expect($unclassified)->toBe([]);
});

test('every plain-text exemption names a translatable field that is not sanitized', function (): void {
    $stale = [];

    foreach (PLAIN_TEXT_TRANSLATIONS as $class => $fields) {
        $model = new $class;

        foreach (array_keys($fields) as $field) {
            if (! in_array($field, $model->getTranslatableAttributes(), true)
                || in_array($field, sanitizedTranslations($model), true)) {
                $stale[] = "{$class}::\${$field}";
            }
        }
    }

    expect($stale)->toBe([]);
});
