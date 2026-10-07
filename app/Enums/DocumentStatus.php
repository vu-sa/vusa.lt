<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/**
 * Whether a SharePoint archive file is shown on vusa.lt. Discovery creates rows as `Pending`;
 * only `Published` rows carry a public link and reach the public site and search.
 */
enum DocumentStatus: string
{
    use HasEnumHelpers;

    case Pending = 'pending';
    case Published = 'published';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Laukia'),
            self::Published => __('Paskelbtas'),
            self::Hidden => __('Paslėptas'),
        };
    }
}
