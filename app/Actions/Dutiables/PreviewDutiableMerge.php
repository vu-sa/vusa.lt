<?php

namespace App\Actions\Dutiables;

use App\Models\Pivots\Dutiable;
use Illuminate\Database\Eloquent\Collection;

class PreviewDutiableMerge
{
    /** @param Collection<int, Dutiable> $rows */
    public static function execute(Collection $rows): array
    {
        $rows = $rows->sortBy(fn (Dutiable $row) => $row->start_date->toDateString().'|'.$row->id)->values();
        $seats = Dutiable::query()->with('duty')
            ->whereIn('via_dutiable_id', $rows->modelKeys())->orderBy('id')->get();

        return [
            'start_date' => $rows->first()->start_date->toDateString(),
            'end_date' => $rows->contains(fn (Dutiable $row) => $row->end_date === null) ? null : $rows->max('end_date')->toDateString(),
            'derived' => $seats->map(fn (Dutiable $seat) => [
                'id' => $seat->id, 'duty_name' => $seat->duty?->name, 'tenant_id' => $seat->tenant_id,
                'start_date' => $seat->start_date->toDateString(), 'end_date' => $seat->end_date?->toDateString(),
            ])->all(),
            'fingerprint' => hash('sha256', json_encode([$rows->map->getAttributes()->all(), $seats->map->getAttributes()->all()])),
        ];
    }
}
