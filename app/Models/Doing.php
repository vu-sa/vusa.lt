<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasComments;
use Illuminate\Support\Carbon;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Doing extends Model
{
    use HasFactory, HasComments, HasRelationships, HasUlids, LogsActivity, SoftDeletes;

    protected $with = ['types'];

    protected $guarded = [];

    protected $casts = [
        // 'created_at' => 'timestamp'
        'created_at' => 'datetime:Y-m-d H:i',
        'extra_attributes' => 'array'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function doables()
    {
        return $this->hasMany(Doable::class);
    }

    public function goals()
    {
        return $this->morphedByMany(Goal::class, 'doable', 'doables');
    }

    public function matters()
    {
        return $this->morphedByMany(InstitutionMatter::class, 'doable', 'doables');
    }

    public function types()
    {
        return $this->morphToMany(Type::class, 'typeable');
    }

    public function documents()
    {
        return $this->morphMany(SharepointDocument::class, 'documentable');
    }

    public function tasks()
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // public function getNeedsAttentionAttribute() {
    //     if ($this->status === 'Pabaigtas') {
    //         return false;
    //     }

    //     $now = Carbon::now();
    //     $date = Carbon::parse($this->date);

    //     if ($now->gt($date)) {
    //         return true;
    //     }

    //     return true;
    // }
}
