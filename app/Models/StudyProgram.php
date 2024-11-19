<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class StudyProgram extends Model
{
    use HasTranslations, HasUlids;

    public $translatable = ['name'];
}
