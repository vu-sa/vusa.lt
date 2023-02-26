<?php

namespace App\Models;

use App\Models\Traits\HasComments;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Contact extends Model
{
    use HasFactory, HasUlids, HasComments, LogsActivity, SoftDeletes;

    public $fillable = [
        'name',
        'email',
        'phone',
        'extra_attributes',
    ];

    protected $casts = [
        'extra_attributes' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function duties()
    {
        return $this->morphedByMany(Duty::class, 'dutiable');
    }
}
