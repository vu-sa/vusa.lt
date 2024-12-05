<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use App\Models\Traits\HasUnitRelation;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Training extends Model
{
    /** @use HasFactory<\Database\Factories\TrainingFactory> */
    use HasFactory, HasRelationships, HasTranslations, HasUlids, HasUnitRelation, Searchable;

    protected $fillable = [
        'name',
        'description',
        'institution_id',
        'start_time',
        'address',
        'end_time',
        'meeting_url',
        'image',
        'max_participants',
        'is_online',
        'is_hybrid',
    ];

    public $translatable = ['name', 'description'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_online' => 'boolean',
        'is_hybrid' => 'boolean',
    ];

    public function toSearchableArray()
    {
        return [
            'name->'.app()->getLocale() => $this->getTranslation('name', app()->getLocale()),
        ];
    }

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function tenant()
    {
        return $this->hasOneDeepFromRelations($this->institution(), (new Institution)->tenant());
    }

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}