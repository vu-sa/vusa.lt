<?php

namespace App\Actions\Duties;

use App\Actions\Dutiables\SyncExOfficioTargets;
use App\Models\Duty;
use App\Models\User;
use App\Services\DutyAssignmentService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/** The one create path for the duty page and the duty wizard. */
class CreateDuty
{
    public function __construct(private readonly DutyAssignmentService $assignments) {}

    /**
     * @param  array<string, mixed>  $data  StoreDutyRequest::validated()
     */
    public function execute(array $data, User $actor): Duty
    {
        return DB::transaction(function () use ($data, $actor): Duty {
            $duty = new Duty;
            $duty->fill(Arr::except($data, ['types', 'current_users', 'ex_officio_target_duty_ids', 'assignable_tenants']))->save();

            $duty->syncAudited('types', $data['types'] ?? []);
            SyncExOfficioTargets::execute($duty, $data['ex_officio_target_duty_ids'] ?? []);
            $duty->assignableTenants()->sync($this->assignments->buildAssignableTenantsSync($data['assignable_tenants'] ?? []));
            $this->assignments->syncRepresentatives($duty, $data['current_users'] ?? [], actor: $actor);

            return $duty->load('institution', 'types', 'current_users');
        });
    }
}
