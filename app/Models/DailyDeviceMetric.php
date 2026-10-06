<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $date
 * @property int $phone_logins
 * @property int $tablet_logins
 * @property int $desktop_logins
 * @property int $pwa_launches
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Database\Factories\DailyDeviceMetricFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyDeviceMetric newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyDeviceMetric newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyDeviceMetric query()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'date',
    'phone_logins',
    'tablet_logins',
    'desktop_logins',
    'pwa_launches',
])]
class DailyDeviceMetric extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'phone_logins' => 'integer',
            'tablet_logins' => 'integer',
            'desktop_logins' => 'integer',
            'pwa_launches' => 'integer',
        ];
    }
}
