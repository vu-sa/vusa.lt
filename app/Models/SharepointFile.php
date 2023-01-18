<?php

namespace App\Models;

use App\Models\Traits\HasComments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SharepointFile extends Model
{
    use HasFactory, HasUuids, HasComments;
    
    public $timestamps = false;

    protected $guarded = [];
}
