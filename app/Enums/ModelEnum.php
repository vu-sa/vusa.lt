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
    case CATEGORY = 'category';
    case COMMENT = 'comment';
    case DOCUMENT = 'document';
    case DUTIABLE = 'dutiable';
    case DUTY = 'duty';
    case FILE = 'file';
    case FORM = 'form';
    case INSTITUTION = 'institution';
    case MEETING = 'meeting';
    case MEMBERSHIP = 'membership';
    case NAVIGATION = 'navigation';
    case NEWS = 'news';
    case QUICK_LINK = 'quick_link';
    case PAGE = 'page';
    case PERMISSION = 'permission';
    case PROBLEM = 'problem';
    case RELATIONSHIP = 'relationship';
    case RELATIONSHIPABLE = 'relationshipable';
    case RESERVATION = 'reservation';
    case RESERVATION_RESOURCE = 'reservation_resource';
    case RESOURCE = 'resource';
    case ROLE = 'role';
    case SHAREPOINT_FILE = 'sharepoint_file';
    case SHAREPOINT_FILEABLE = 'sharepoint_fileable';
    case STUDY_PROGRAM = 'study_program';
    case STUDY_SET = 'study_set';
    case SURVEY = 'survey';
    case TAG = 'tag';
    case TASK = 'task';
    case TENANT = 'tenant';
    case TRAINING = 'training';
    case TYPE = 'type';
    case USER = 'user';

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
        $class = 'App\\Models\\'.Str::studly(Str::singular($pluralizedModel));

        return class_exists($class) && is_subclass_of($class, Model::class) ? $class : null;
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
     * Get the allowed permission scopes for a specific model.
     * Models not listed here will have all scopes (own, padalinys, *).
     */
    public static function getAllowedScopes(string $model): array
    {
        $scopeRestrictions = [
            // Global/system-wide models that don't belong to users or padaliniai
            'tags' => ['*'],
            'types' => ['*'],
            'categories' => ['*'],
            'permissions' => ['*'],
            'roles' => ['*'],
            'navigations' => ['*'],

            // Models that belong to padaliniai but not to individual users
            'studyPrograms' => ['padalinys', '*'],
            'studySets' => ['padalinys', '*'],
            'quickLinks' => ['padalinys', '*'],
            'problems' => ['padalinys', '*'],

            // A survey belongs to a padalinys, never to one person: "own" would let the
            // author approve their own survey, which is the one thing the flow prevents.
            'surveys' => ['padalinys', '*'],

            // Special case: institutions allow "own" scope only for read operations
            // This is handled in the InstitutionPolicy and a special case in the seeder
            'institutions' => ['own', 'padalinys', '*'],

            // Add more restrictions as needed
        ];

        return $scopeRestrictions[$model] ?? ['own', 'padalinys', '*'];
    }

    /**
     * Check if a model has a specific scope.
     */
    public static function hasScope(string $model, string $scope): bool
    {
        return in_array($scope, self::getAllowedScopes($model));
    }
}
