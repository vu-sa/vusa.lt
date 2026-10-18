<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $taken_on
 * @property int $window_days
 * @property array<string, array{value: float|null, part: int|null, whole: int|null}> $metrics
 * @property bool $is_baseline
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Database\Factories\RepMetricSnapshotFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RepMetricSnapshot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RepMetricSnapshot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RepMetricSnapshot query()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'taken_on',
    'window_days',
    'metrics',
    'is_baseline',
])]
class RepMetricSnapshot extends Model
{
    use HasFactory;

    public static function baseline(): ?self
    {
        return self::query()->where('is_baseline', true)->first();
    }

    public function value(string $metric): ?float
    {
        $value = $this->metrics[$metric]['value'] ?? null;

        return $value === null ? null : (float) $value;
    }

    protected function casts(): array
    {
        return [
            'taken_on' => 'date:Y-m-d',
            'window_days' => 'integer',
            'metrics' => 'array',
            'is_baseline' => 'boolean',
        ];
    }
}
