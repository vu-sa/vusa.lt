<?php

use App\Enums\InstitutionActivityStatus;

test('only overdue, inactive and approaching institutions need attention, most urgent first', function (): void {
    $needingAttention = collect(InstitutionActivityStatus::cases())
        ->filter(fn (InstitutionActivityStatus $status) => $status->requiresAction())
        ->sortByDesc(fn (InstitutionActivityStatus $status) => $status->priority())
        ->map(fn (InstitutionActivityStatus $status) => $status->value)
        ->values()
        ->all();

    expect($needingAttention)->toBe(['overdue', 'no_activity', 'approaching']);
});
