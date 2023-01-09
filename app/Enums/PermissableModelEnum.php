<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;
// ...in theory, i could have it managed in the database, and check for each model
// if appropriate permissions are created

/**
 * @method static self agendaItem()
 * @method static self banner()
 * @method static self calendar()
 * @method static self category()
 * @method static self comment()
 * @method static self contact()
 * @method static self doing()
 * @method static self duty()
 * @method static self goal()
 * @method static self goalGroup()
 * @method static self institution()
 * @method static self mainPage()
 * @method static self matter()
 * @method static self meeting()
 * @method static self navigation()
 * @method static self news()
 * @method static self notification()
 * @method static self page()
 * @method static self relationship()
 * @method static self saziningaiExam()
 * @method static self saziningaiExamFlow()
 * @method static self saziningaiObserver()
 * @method static self sharePointDocument()
 * @method static self tag()
 * @method static self task()
 * @method static self type()
 * @method static self user()
 */

final class PermissableModelEnum extends Enum {}