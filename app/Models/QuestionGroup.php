<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Staudenmeir\EloquentHasManyDeep\HasRelationships;


class QuestionGroup extends Model
{
    use HasFactory, HasRelationships;

    protected $guarded = [];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function doings()
    {
        return $this->hasManyDeepFromRelations($this->questions(), (new Question)->doings());
    }
}
