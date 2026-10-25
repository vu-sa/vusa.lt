<?php

namespace App\Actions;

use App\Actions\Media\SyncImageMedia;
use App\Models\Institution;
use App\Models\User;
use App\Services\InstitutionDeactivationService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Enums\ActivityEvent;

class UpdateInstitution
{
    public function __construct(private readonly InstitutionDeactivationService $deactivation, private readonly SyncImageMedia $syncImage) {}

    /** @param array<string, mixed> $data */
    public function execute(Institution $institution, array $data, User $actor): void
    {
        DB::transaction(function () use ($institution, $data, $actor): void {
            $institution->refreshForUpdate();
            $closing = (bool) $institution->is_active && array_key_exists('is_active', $data) && ! $data['is_active'];
            $closedIds = $closing ? $this->deactivation->closeTerms($institution) : [];

            $institution->fill(Arr::except($data, ['tenant_id', 'types', 'image_media', 'logo_media']));
            if ($actor->isSuperAdmin()) {
                $institution->fill(Arr::only($data, ['tenant_id']));
            }
            $institution->save();
            if (array_key_exists('types', $data)) {
                $institution->syncAudited('types', $data['types'] ?? []);
            }
            $this->syncImage->fromValidated($institution, 'image', $data, 'image_media', $actor);
            $this->syncImage->fromValidated($institution, 'logo', $data, 'logo_media', $actor);

            if ($closing) {
                activity()->performedOn($institution)->causedBy($actor)->event(ActivityEvent::Updated)
                    ->withProperties(['reason' => 'institution_deactivation', 'end_date' => today()->subDay()->toDateString(), 'dutiable_ids' => $closedIds])
                    ->log('institution_deactivated');
            }
        }, 3);
    }
}
