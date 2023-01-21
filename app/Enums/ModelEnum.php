<?php

namespace App\Enums;

use App\Enums\Traits\HasCamelCaseLabels;
use Spatie\Enum\Laravel\Enum;

/**
 * @typescript
 * @method static self AGENDA_ITEM()
 * @method static self BANNER()
 * @method static self CALENDAR()
 * @method static self CATEGORY()
 * @method static self COMMENT()
 * @method static self CONTACT()
 * @method static self DOING()
 * @method static self DUTIABLE()
 * @method static self DUTY()
 * @method static self GOAL_GROUP()
 * @method static self GOAL()
 * @method static self INSTITUTION()
 * @method static self MAIN_PAGE()
 * @method static self MATTER()
 * @method static self MEETING()
 * @method static self NAVIGATION()
 * @method static self NEWS()
 * @method static self PAGE()
 * @method static self PERMISSION()
 * @method static self RELATIONSHIP()
 * @method static self RELATIONSHIPABLE()
 * @method static self ROLE()
 * @method static self SAZININGAI_EXAM()
 * @method static self SAZININGAI_EXAM_FLOW()
 * @method static self SAZININGAI_EXAM_OBSERVER()
 * @method static self SHAREPOINT_FILE()
 * @method static self SHAREPOINT_FILEABLE()
 * @method static self TAG()
 * @method static self TASK()
 * @method static self TYPE()
 * @method static self USER()
 */

final class ModelEnum extends Enum {
    use hasCamelCaseLabels;
}