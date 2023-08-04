<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media as ModelsMedia;

// This is needed so typescript transformer gets this model
class Media extends ModelsMedia {}
