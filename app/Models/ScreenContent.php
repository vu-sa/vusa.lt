<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;

class ScreenContent extends Model
{
    use HasFactory, InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('screen_content')
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'video/mp4'])
            ->useDisk('spatieMediaLibrary')
            ->withResponsiveImages();
    }
}
