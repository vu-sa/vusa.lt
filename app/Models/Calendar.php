<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\MediaCollections\File;

class Calendar extends Model implements HasMedia
{
    
    use HasFactory, InteractsWithMedia, Searchable;

    protected $table = 'calendar';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'attributes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function padalinys()
    {
        return $this->belongsTo(Padalinys::class, 'padalinys_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category', 'alias');
    }

    public function registrationForm()
    {
        return $this->belongsTo(RegistrationForm::class, 'registration_form_id', 'id');
    }

    public function registerMediaCollections(): void
    {
        $this
        ->addMediaCollection('images')
        ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png'])
        ->useDisk('spatieMediaLibrary')
        ->withResponsiveImages();
    }

    public function toSearchableArray()
    {
        $array = $this->toArray();

        // Customize array...
        // return only title
        $array = [
            'title' => $this->title,
        ];

        return $array;
    }
    
}
