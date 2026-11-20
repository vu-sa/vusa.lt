<?php

/**
 * Every column whose name says it may hold markup strips a `<script>` the moment it is set,
 * translatable or not, or is listed below. Behaviour, not configuration: a list, a mutator or any
 * future mechanism all pass, and a new Tiptap-authored column cannot reach `v-html` raw by default.
 */

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

// The schema is what decides which columns are checked.
pest()->use(RefreshDatabase::class);

/** Last segments that make a column an identifier, whatever comes before (`comment_id`, `short_name`). */
const IDENTIFIER_SEGMENTS = ['at', 'count', 'hash', 'id', 'name', 'type', 'url', 'version'];

/** Name segments that suggest a column may hold markup. */
const RICH_COLUMN_SEGMENTS = ['about', 'answer', 'bio', 'body', 'comment', 'content', 'description', 'details', 'evaluation', 'excerpt', 'html', 'instructions', 'intro', 'lead', 'message', 'note', 'notes', 'position', 'result', 'short', 'solution', 'steps', 'summary', 'text'];

/**
 * Columns no Tiptap editor writes and no `v-html` renders. Keyed `table.column`; each names its editor.
 *
 * @var array<string, string>
 */
const PLAIN_TEXT_COLUMNS = [
    'activity_log.description' => 'log message written by code',
    'agenda_items.description' => 'plain input',
    'agenda_items.student_position' => 'plain input',
    'approval_flows.steps' => 'JSON step configuration',
    'approvals.notes' => 'plain input',
    'approvals.reversion_notes' => 'plain input',
    'documents.summary' => 'synced from SharePoint metadata',
    'documents.sync_error_message' => 'error text written by code',
    'dutiables.study_program_note' => 'plain input',
    'event_types.description' => 'MultiLocaleInput textarea',
    'fileable_files.description' => 'plain input',
    'goals.expected_result' => 'plain input; description and evaluation are the Tiptap fields',
    'institution_activity_requests.answer' => 'InstitutionActivityAnswer value',
    'institution_activity_requests.note' => 'plain input',
    'institution_check_ins.note' => 'plain input',
    'knowledge_sections.description' => 'plain input',
    'learning_audiences.description' => 'Textarea in LearningAudienceSheet',
    'lecturer_reviews.comment' => 'plain input',
    'mailing_list_exclusions.note' => 'plain input',
    'mailing_list_messages.body_text' => 'plain-text part of a received email',
    'mailing_list_sources.note' => 'plain input',
    'mailing_lists.description' => 'plain input',
    'meetings.description' => 'plain input',
    'pages.meta_description' => 'meta tag text',
    'problem_categories.description' => 'plain input',
    'quick_links.text' => 'link label',
    'reservation_drafts.description' => 'plain input',
    'reservations.description' => 'plain input',
    'resource_categories.description' => 'plain input',
    'resources.description' => 'plain input in ResourceForm',
    'roles.description' => 'plain input',
    'study_sets.description' => 'Textarea in StudySetForm',
    'support_requests.description' => 'plain input',
    'support_requests.selected_text' => 'text the reporter selected on the page',
    'tasks.description' => 'plain input',
    'text_box_submissions.text' => 'plain input',
    'types.description' => 'legacy type, no edit form',
    'votes.note' => 'plain input',
];

/**
 * Columns sanitized later than `setAttribute()`, each with the test that proves it.
 *
 * @var array<string, string>
 */
const SANITIZED_ELSEWHERE = [
    'content_parts.json_content' => 'ContentPart saving hook; RichTextSanitizationTest "content part json_content"',
];

/**
 * @return list<class-string<Model>>
 */
function concreteModelClasses(): array
{
    return collect(File::allFiles(app_path('Models')))
        ->map(fn (SplFileInfo $file): string => 'App\\Models\\'.Str::of($file->getRelativePathname())->beforeLast('.php')->replace('/', '\\'))
        ->filter(fn (string $class): bool => class_exists($class) && is_subclass_of($class, Model::class) && ! (new ReflectionClass($class))->isAbstract())
        ->values()
        ->all();
}

/**
 * @return array<string, class-string<Model>> `table.column` => the first model that writes it
 */
function richColumns(): array
{
    $columns = [];

    foreach (concreteModelClasses() as $class) {
        $table = (new $class)->getTable();

        if (! Schema::hasTable($table)) {
            continue;
        }

        foreach (Schema::getColumns($table) as $column) {
            $isText = in_array($column['type_name'], ['text', 'varchar', 'mediumtext', 'longtext', 'tinytext', 'char'], true);

            $segments = explode('_', $column['name']);

            if ($isText && ! in_array(end($segments), IDENTIFIER_SEGMENTS, true) && array_intersect($segments, RICH_COLUMN_SEGMENTS) !== []) {
                $columns["{$table}.{$column['name']}"] ??= $class;
            }
        }
    }

    ksort($columns);

    return $columns;
}

test('every rich-named column strips a script when set, or is listed', function (): void {
    $unsanitized = [];

    foreach (richColumns() as $key => $class) {
        if (isset(PLAIN_TEXT_COLUMNS[$key]) || isset(SANITIZED_ELSEWHERE[$key])) {
            continue;
        }

        $model = new $class;
        $column = Str::after($key, '.');

        try {
            $model->setAttribute($column, '<p>x</p><script>alert(1)</script>');
            $stored = (string) json_encode($model->getAttributes()[$column] ?? null);
        } catch (Throwable) {
            $stored = '<script';
        }

        if (str_contains($stored, '<script')) {
            $unsanitized[] = $key;
        }
    }

    expect($unsanitized)->toBe([]);
});

test('every listed column still exists', function (): void {
    $known = array_keys(richColumns());

    expect(array_values(array_diff([...array_keys(PLAIN_TEXT_COLUMNS), ...array_keys(SANITIZED_ELSEWHERE)], $known)))->toBe([]);
});
