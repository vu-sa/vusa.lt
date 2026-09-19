<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property CarbonInterface $date
 * @property int $phone_logins
 * @property int $tablet_logins
 * @property int $desktop_logins
 * @property int $pwa_launches
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class DailyDeviceMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'phone_logins',
        'tablet_logins',
        'desktop_logins',
        'pwa_launches',
    ];

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
