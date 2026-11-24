<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\Institution;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetDutyActive
{
    public function execute(Duty $duty, bool $active): void
    {
        DB::transaction(function () use ($duty, $active): void {
            Institution::withTrashed()->whereKey($duty->institution_id)->lockForUpdate()->first();
            $duty->refreshForUpdate();

            if (! $active && $duty->dutiables()->notEnded()->exists()) {
                throw ValidationException::withMessages([
                    'is_active' => __('dutiables.assign.finish_terms_before_retiring'),
                ]);
            }

            $duty->update(['is_active' => $active]);
        }, 3);
    }
}
