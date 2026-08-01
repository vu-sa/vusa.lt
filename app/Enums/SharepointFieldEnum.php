<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

enum SharepointFieldEnum: string
{
    use HasEnumHelpers;

    case PADALINYS = 'PADALINYS';
    case TITLE = 'TITLE';
    case DATE = 'DATE';
    case EFFECTIVE_DATE = 'EFFECTIVE_DATE';
    case EXPIRATION_DATE = 'EXPIRATION_DATE';
    case LANGUAGE = 'LANGUAGE';
    case TURINYS = 'TURINYS';
    case SUMMARY = 'SUMMARY';

    public function label(): string
    {
        return match ($this) {
            self::PADALINYS => 'Padalinys',
            self::TITLE => 'Title',
            self::DATE => 'Date',
            self::EFFECTIVE_DATE => 'Effective_x0020_Date',
            self::EXPIRATION_DATE => 'Expiration_x0020_Date0',
            self::LANGUAGE => 'Language',
            self::TURINYS => 'Turinys',
            self::SUMMARY => 'Summary',
        };
    }
}
