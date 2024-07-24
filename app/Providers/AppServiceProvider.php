<?php

namespace App\Providers;

use App\Http\Middleware\TrimStrings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use RalphJSmit\Laravel\SEO\Facades\SEOManager;
use RalphJSmit\Laravel\SEO\Support\OpenGraphTag;
use RalphJSmit\Laravel\SEO\Support\Tag;
use RalphJSmit\Laravel\SEO\TagCollection;
use RalphJSmit\Laravel\SEO\Tags\OpenGraphTags;
use RalphJSmit\Laravel\SEO\Tags\TwitterCard\SummaryLargeImage;
use RalphJSmit\Laravel\SEO\Tags\TwitterCardTags;
use Spatie\Translatable\Facades\Translatable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(125);

        // Needed for json_content in Content model
        TrimStrings::skipWhen(function (Request $request) {
            return $request->is('mano/*');
        });

        Translatable::fallback(
            fallbackLocale: 'lt'
        );

        SEOManager::tagTransformer(function (TagCollection $tags): TagCollection {
            $tags = $tags->map(function ($tag) {
                if (is_subclass_of($tag, Tag::class)) {
                    $tag->attributes['inertia'] = '';
                } elseif ($tag::class === TwitterCardTags::class) {
                    foreach ($tag as $item) {
                        if ($item::class === SummaryLargeImage::class) {
                            foreach ($item as $subItem) {
                                $subItem->attributes['inertia'] = '';
                            }
                        } else {
                            $item->attributes['inertia'] = '';
                        }
                    }
                } else {
                    foreach ($tag as $item) {
                        /*if ($item::class === OpenGraphTag::class) {*/
                        /*    // if property 'image', cast, since ->html is protected*/
                        /*    if ($item->attributes['property'] === 'image') {*/
                        /*        $array = (array)$item->attributes['content'];*/
                        /**/
                        /*        // set first value as 'content'*/
                        /*        $item->attributes['image_content'] = collect($array)->first();*/
                        /*    }*/
                        /*}*/
                        $item->attributes['inertia'] = '';

                    }
                }

                return $tag;
            });

            return $tags;
        });
    }
}
