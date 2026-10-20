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
 * @property string $scope_key
 * @property int|null $tenant_id
 * @property int $definition_version
 * @property bool $is_reconstructed
 * @property Carbon|null $measured_at
 * @property array<string, array{part: list<string>, whole: list<string>}>|null $contributors
 * @property array|null $supplementary
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
    'scope_key', 'tenant_id', 'definition_version', 'is_reconstructed', 'measured_at', 'contributors', 'supplementary',
])]
class RepMetricSnapshot extends Model
{
    use HasFactory;

    public static function baseline(): ?self
    {
        return self::query()->where('scope_key', 'global')->where('is_baseline', true)->first();
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
            'is_reconstructed' => 'boolean',
            'measured_at' => 'datetime',
            'contributors' => 'array',
            'supplementary' => 'array',
        ];
    }
}
