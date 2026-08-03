<?php

namespace App\Http\Resources;

use App\Models\Activity;
use App\Models\User;
use App\Support\Auditables;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Activity
 *
 * The activity-log feed shape consumed by the ActivityLogViewer frontend
 * (mirrors resources/js/Types/activityLog.ts). Expects the collection to have
 * already been through App\Services\ActivityChangeFormatter::prepare(), which
 * stashes `formatted_changes` / `formatted_subject_label` on each model --
 * this resource only arranges that data, it does no formatting itself so a
 * whole page's relation lookups can be resolved once instead of per-row.
 */
class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        $isRoot = $this->subject_type === $this->root_subject_type
            && (string) $this->subject_id === (string) $this->root_subject_id;

        // Causer is polymorphic in principle, but in practice only a User can
        // cause an admin action -- narrowed locally (not inside the when()
        // closure below) so it stays a typed User, not the generic Model the
        // MorphTo relation resolves to.
        $causer = $this->causer;
        $causerData = $causer instanceof User ? [
            'id' => (string) $causer->id,
            'name' => $causer->name,
            'profile_photo_path' => $causer->profile_photo_path,
        ] : null;

        return [
            'id' => $this->id,
            'event' => $this->event,
            'created_at' => $this->created_at?->toISOString(),
            'causer' => $this->when($causerData !== null, fn () => $causerData),
            'subject' => [
                'type' => Auditables::aliasFor($this->subject_type) ?? $this->subject_type,
                'id' => (string) $this->subject_id,
                'label' => $this->getAttribute('formatted_subject_label') ?? (string) $this->subject_id,
                'is_root' => $isRoot,
            ],
            'changes' => $this->getAttribute('formatted_changes') ?? [],
            'relation_change' => $this->when(
                $this->event === 'relation_updated',
                fn () => $this->getAttribute('formatted_relation_change')
            ),
        ];
    }
}
