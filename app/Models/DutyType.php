<?php

namespace App\Models;

use App\Contracts\GuardsForceDelete;
use App\Contracts\SharepointFileableContract;
use App\Events\FileableNameUpdated;
use App\Models\Pivots\DutyDutyType;
use App\Models\Pivots\DutyTypeRole;
use App\Models\Traits\GuardsForceDeleteWhenReferenced;
use App\Models\Traits\HasSharepointFiles;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\HasTypeHierarchy;
use App\Models\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'description', 'slug', 'parent_id', 'extra_attributes'])]
class DutyType extends Model implements GuardsForceDelete, SharepointFileableContract
{
    use GuardsForceDeleteWhenReferenced, HasFactory, HasSharepointFiles, HasTranslations, HasTypeHierarchy, LogsModelActivity, SoftDeletes;

    protected $translatable = ['title', 'description'];

    protected function casts(): array
    {
        return ['extra_attributes' => 'array'];
    }

    protected function sanitizedHtmlTranslations(): array
    {
        return ['description'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $type): void {
            if ($type->isDirty('title')) {
                FileableNameUpdated::dispatch($type);
            }
        });
    }

    public function duties(): BelongsToMany
    {
        return $this->belongsToMany(Duty::class)->using(DutyDutyType::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->using(DutyTypeRole::class);
    }

    public function forceDeleteBlockedReason(): ?string
    {
        return $this->forceDeleteReasonFor([
            'trash.blockers.type_assignments' => $this->duties()->withTrashed()->count(),
            'entities.role.model' => $this->roles()->count() + \Illuminate\Support\Facades\DB::table('role_can_attach_duty_types')->where('duty_type_id', $this->id)->count(),
            'entities.dutyType.model' => $this->descendants()->withTrashed()->count(),
        ]);
    }
}
