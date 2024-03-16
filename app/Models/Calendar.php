<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Calendar extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, Searchable;

    protected $table = 'calendar';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'date' => 'datetime',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'extra_attributes' => 'array',
    ];

    public function padalinys()
    {
        return $this->belongsTo(Padalinys::class, 'padalinys_id');
    }

    //# Undefined property: App\Models\Calendar::$category, when generating types
    public function categoryR(): BelongsTo
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
