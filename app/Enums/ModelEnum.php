<?php

namespace App\Enums;

use App\Enums\Traits\HasCamelCaseLabels;
use Spatie\Enum\Laravel\Enum;

/**
 * @typescript
 *
 * @method static self AGENDA_ITEM()
 * @method static self BANNER()
 * @method static self CALENDAR()
 * @method static self CATEGORY()
 * @method static self CHANGELOG_ITEM()
 * @method static self COMMENT()
 * @method static self DOCUMENT()
 * @method static self DOING()
 * @method static self DUTIABLE()
 * @method static self DUTY()
 *                            File is not a model, but it's used for generating file permissions
 * @method static self FILE()
 * @method static self FORM()
 * @method static self GOAL_GROUP()
 * @method static self GOAL()
 * @method static self INSTITUTION()
 * @method static self MATTER()
 * @method static self MEETING()
 * @method static self MEMBERSHIP()
 * @method static self NAVIGATION()
 * @method static self NEWS()
 * @method static self QUICK_LINK()
 * @method static self PAGE()
 * @method static self PERMISSION()
 * @method static self RELATIONSHIP()
 * @method static self RELATIONSHIPABLE()
 * @method static self RESERVATION()
 * @method static self RESERVATION_RESOURCE()
 * @method static self RESOURCE()
 * @method static self ROLE()
 * @method static self SHAREPOINT_FILE()
 * @method static self SHAREPOINT_FILEABLE()
 * @method static self STUDY_PROGRAM()
 * @method static self TAG()
 * @method static self TASK()
 * @method static self TENANT()
 * @method static self TRAINING()
 * @method static self TYPE()
 * @method static self USER()
 */
final class ModelEnum extends Enum
{
    use hasCamelCaseLabels;

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
            'quickLinks' => ['padalinys', '*'],

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
