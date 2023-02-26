<?php

namespace App\Models;

use App\Models\Traits\HasContentRelationships;
use App\Models\Traits\HasSharepointFiles;
use App\Services\ResourceServices\SharepointFileService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;

class Type extends Model
{
    use HasFactory, HasContentRelationships, HasSharepointFiles, LogsActivity, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function institutions()
    {
        return $this->morphedByMany(Institution::class, 'typeable');
    }

    public function duties()
    {
        return $this->morphedByMany(Duty::class, 'typeable');
    }

    public function doings()
    {
        return $this->morphedByMany(Doing::class, 'typeable');
    }

    public function descendants()
    {
        return $this->hasMany(Type::class, 'parent_id');
    }

    public function recursiveDescendants()
    {
        return $this->descendants()->with('recursiveDescendants');
    }

    public function parent()
    {
        return $this->belongsTo(Type::class, 'parent_id');
    }

    public function recursiveParent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->parent()->with('recursiveParent');
    }

    public function pushAndRecursiveDescendants($type, $flattened = null): \Illuminate\Support\Collection
    {
        if (is_null($flattened)) {
            $flattened = collect();
        }

        foreach ($type->recursiveDescendants as $descendant) {
            $this->pushAndRecursiveDescendants($descendant, $flattened);
        }
        // remove recursivedescendants
        $flattened->push($type);
        $flattened->forget('recursiveDescendants');

        return $flattened;
    }

    public function getDescendantsAndSelf(): \Illuminate\Support\Collection
    {
        // Because the descendants were pushed at the end, we need to reverse it
        return $this->pushAndRecursiveDescendants($this)->reverse()->values();
    }

    public function pushAndRecursiveParents($type, $flattened = null): \Illuminate\Support\Collection
    {
        if (is_null($flattened)) {
            $flattened = collect();
        }

        if ($parent = $type->recursiveParent) {
            $this->pushAndRecursiveParents($parent, $flattened);
        }

        // remove recursiveparents
        $flattened->push($type);

        return $flattened;
    }

    public function getParentsAndSelf(): \Illuminate\Support\Collection
    {
        // Because the parents were pushed at the end, we need to reverse it
        return $this->pushAndRecursiveParents($this)->reverse()->values();
    }

    public function allModelsFromModelType()
    {
        if (Str::contains($this->model_type, 'Institution')) {
            return $this->model_type::select('id', 'name', 'padalinys_id')->with('padaliniai')->orderBy('name')->get();
        } elseif (Str::contains($this->model_type, 'Duty')) {
            return $this->model_type::select('id', 'name', 'institution_id')->with('padaliniai')->orderBy('name')->get();
        } elseif (Str::contains($this->model_type, 'Doing')) {
            return $this->model_type::select('id', 'title')->with('padaliniai')->orderBy('title')->get();
        }
    }
}
