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

            if (! $active && $duty->current_users()->exists()) {
                throw ValidationException::withMessages([
                    'is_active' => __('Pirmiau užbaik narių pareigas, tada pažymėk pareigybę neaktyvia.'),
                ]);
            }

            $duty->update(['is_active' => $active]);
        }, 3);
    }
}
