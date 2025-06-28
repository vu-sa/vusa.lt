<?php

namespace App\Models;

use App\Models\Pivots\Dutiable;
use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyProgram extends Model
{
    use HasFactory, HasTranslations, HasUlids;

    public $translatable = ['name'];

    protected $fillable = [
        'name',
        'degree',
        'tenant_id',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function dutiables(): HasMany
    {
        return $this->hasMany(\App\Models\Pivots\Dutiable::class);
    }
}
