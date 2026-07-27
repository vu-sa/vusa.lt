<?php

namespace App\Enums;

use App\Enums\Traits\HasCamelCaseLabels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Enum\Laravel\Enum;

/**
 * @typescript
 *
 * @method static self AGENDA_ITEM()
 * @method static self BANNER()
 * @method static self CALENDAR()
 * @method static self CATEGORY()
 * @method static self COMMENT()
 * @method static self DOCUMENT()
 * @method static self DUTIABLE()
 * @method static self DUTY()
 *                            File is not a model, but it's used for generating file permissions
 * @method static self FILE()
 * @method static self FORM()
 * @method static self INSTITUTION()
 * @method static self MEETING()
 * @method static self MEMBERSHIP()
 * @method static self NAVIGATION()
 * @method static self NEWS()
 * @method static self QUICK_LINK()
 * @method static self PAGE()
 * @method static self PERMISSION()
 * @method static self PROBLEM()
 * @method static self RELATIONSHIP()
 * @method static self RELATIONSHIPABLE()
 * @method static self RESERVATION()
 * @method static self RESERVATION_RESOURCE()
 * @method static self RESOURCE()
 * @method static self ROLE()
 * @method static self SHAREPOINT_FILE()
 * @method static self SHAREPOINT_FILEABLE()
 * @method static self STUDY_PROGRAM()
 * @method static self STUDY_SET()
 * @method static self TAG()
 * @method static self TASK()
 * @method static self TENANT()
 * @method static self TRAINING()
 * @method static self TYPE()
 * @method static self USER()
 */
final class ModelEnum extends Enum
{
    use HasCamelCaseLabels;

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
