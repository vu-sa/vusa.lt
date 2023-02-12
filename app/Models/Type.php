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

    public function getDescendantsAndSelf()
    {
        $descedants = $this->descendants()->get();
        $descedants->push($this);

        return $descedants;
    }

    public function allModelsFromModelType()
    {        
        if (Str::contains($this->model_type, 'Institution')) {
            return $this->model_type::select('id', 'name', 'padalinys_id')->with('padaliniai')->orderBy('name')->get();
        } elseif (Str::contains($this->model_type, 'Duty')) {
            return $this->model_type::select('id', 'name', 'institution_id')->with('padaliniai')->orderBy('name')->get();
        } elseif (Str::contains($this->model_type, 'Doing')) {
            return $this->model_type::select('id', 'title', 'user_id')->with('padaliniai')->orderBy('title')->get();
        }
    }
}
