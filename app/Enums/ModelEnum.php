<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;
use App\Enums\Traits\HasCamelCaseLabels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * File is not a model, but it's used for generating file permissions.
 */
enum ModelEnum: string
{
    use HasCamelCaseLabels;
    use HasEnumHelpers;

    case AGENDA_ITEM = 'agenda_item';
    case BANNER = 'banner';
    case CALENDAR = 'calendar';
    case COMMENT = 'comment';
    case DOCUMENT = 'document';
    case DUTIABLE = 'dutiable';
    case DUTY = 'duty';
    case DUTY_TYPE = 'duty_type';
    case EVENT_TYPE = 'event_type';
    case FILE = 'file';
    case FORM = 'form';
    case GOAL = 'goal';
    case LEARNING_STEP = 'learning_step';
    case KNOWLEDGE_ITEM = 'knowledge_item';
    case INSTITUTION = 'institution';
    case INSTITUTION_TYPE = 'institution_type';
    case MAILING_LIST = 'mailing_list';
    case MEETING = 'meeting';
    case NAVIGATION = 'navigation';
    case NEWS = 'news';
    case QUICK_LINK = 'quick_link';
    case PAGE = 'page';
    case PERMISSION = 'permission';
    case PROBLEM = 'problem';
    case RELATIONSHIP = 'relationship';
    case RESERVATION = 'reservation';
    case RESERVATION_RESOURCE = 'reservation_resource';
    case RESOURCE = 'resource';
    case ROLE = 'role';
    case STUDY_PROGRAM = 'study_program';
    case STUDY_SET = 'study_set';
    case TAG = 'tag';
    case TASK = 'task';
    case TENANT = 'tenant';
    case USER = 'user';

    /**
     * Resolve the enum case for a pluralized permission resource name (e.g. "studyPrograms").
     */
    public static function tryFromPlural(string $pluralizedModel): ?self
    {
        foreach (self::cases() as $case) {
            if (Str::plural($case->label()) === $pluralizedModel) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Resolve the Eloquent model class for a pluralized permission resource name.
     *
     * e.g. "studyPrograms" => App\Models\StudyProgram::class. Returns null when no
     * matching model exists (some resources, such as "files", are permission-only).
     *
     * @return class-string<Model>|null
     */
    public static function getModelClass(string $pluralizedModel): ?string
    {
        $studly = Str::studly(Str::singular($pluralizedModel));

        foreach (['App\\Models\\', 'App\\Models\\Pivots\\'] as $namespace) {
            $class = $namespace.$studly;

            if (class_exists($class) && is_subclass_of($class, Model::class)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * Determine whether a pluralized permission resource maps to a soft-deletable model.
     *
     * Drives which resources receive forceDelete permissions, so the permission list
     * stays in sync with the models automatically instead of via a duplicated list.
     */
    public static function isSoftDeletable(string $pluralizedModel): bool
    {
        $class = self::getModelClass($pluralizedModel);

        return $class !== null && in_array(SoftDeletes::class, class_uses_recursive($class), true);
    }

    /**
     * Scopes this model's policy reads. Exhaustive so a new case cannot fail open to grantable
     * scopes before its record-level semantics are decided.
     *
     * @return list<string>
     */
    public function allowedScopes(): array
    {
        return match ($this) {
            self::RESERVATION_RESOURCE,
            self::TENANT => [],

            self::DUTY_TYPE,
            self::EVENT_TYPE,
            self::INSTITUTION_TYPE,
            self::NAVIGATION,
            self::PERMISSION,
            self::RELATIONSHIP,
            self::ROLE,
            self::TAG => ['*'],

            self::AGENDA_ITEM,
            self::BANNER,
            self::CALENDAR,
            self::COMMENT,
            self::DUTIABLE,
            self::FILE,
            self::FORM,
            self::GOAL,
            self::INSTITUTION,
            self::KNOWLEDGE_ITEM,
            self::LEARNING_STEP,
            self::MAILING_LIST,
            self::NEWS,
            self::PAGE,
            self::PROBLEM,
            self::QUICK_LINK,
            self::RESERVATION,
            self::RESOURCE,
            self::STUDY_PROGRAM,
            self::STUDY_SET,
            self::TASK,
            self::USER => ['padalinys', '*'],

            self::DOCUMENT,
            self::DUTY,
            self::MEETING => ['own', 'padalinys', '*'],
        };
    }

    /**
     * Get the allowed permission scopes for a pluralized permission resource.
     *
     * @return list<string>
     */
    public static function getAllowedScopes(string $model): array
    {
        return self::tryFromPlural($model)?->allowedScopes() ?? [];
    }

    /**
     * Check if a model has a specific scope.
     */
    public static function hasScope(string $model, string $scope): bool
    {
        return in_array($scope, self::getAllowedScopes($model), true);
    }
}
