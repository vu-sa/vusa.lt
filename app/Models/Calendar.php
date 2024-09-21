<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Calendar extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia, Searchable;

    protected $table = 'calendar';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        // IMPORTANT: just transform date always to datetime, don't keep as number, as problems arise
        'date' => 'datetime:Y-m-d H:i',
        'end_date' => 'datetime:Y-m-d H:i',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    public $translatable = [
        'title',
        'description',
        'location',
        'organizer',
        'cto_url',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    //# Undefined property: App\Models\Calendar::$category, when generating types
    // "@php artisan model:typer --global > resources/js/Types/models.d.ts"
    // sail artisan model:typer --optional-relations --optional-nullables --global > resources/js/Types/models.d.ts
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category', 'alias', 'category');
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
