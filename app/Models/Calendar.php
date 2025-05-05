<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Datetime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;
use Spatie\CalendarLinks\Link;
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
        'is_draft' => 'boolean',
    ];

    public $translatable = [
        'title',
        'description',
        'location',
        'organizer',
        'cto_url',
    ];

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
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
        return [
            'id' => (string) $this->id,
            'title->lt' => $this->getTranslation('title', 'lt'),
            'title->en' => $this->getTranslation('title', 'en'),
            'location' => $this->location,
            'organizer' => $this->organizer,
            'date' => $this->date ? $this->date->timestamp : null,
            'end_date' => $this->end_date ? $this->end_date->timestamp : null,
            'lang' => $this->lang ?? app()->getLocale(),
            'tenant_id' => $this->tenant_id,
            'is_draft' => $this->is_draft,
            'created_at' => $this->created_at->timestamp,
            'updated_at' => $this->updated_at->timestamp,
        ];
    }

    // TODO: add all pages to dev seed
    public function googleLink(): ?string
    {
        // check if event date is after end date, if so, return null
        // TODO: check in frontend
        if ($this->end_date && $this->date > $this->end_date) {
            return null;
        }

        return Link::create(
            $this->title,
            DateTime::createFromFormat('Y-m-d H:i:s', $this->date),
            $this->end_date
                ? DateTime::createFromFormat('Y-m-d H:i:s', $this->end_date)
                : Carbon::parse($this->date)->addHour()->toDateTime()
        )
            ->description(strip_tags($this->description))
            ->address($this->location ?? '')
            ->google();
    }
}
