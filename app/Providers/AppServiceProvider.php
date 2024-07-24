<?php

namespace App\Providers;

use App\Http\Middleware\TrimStrings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use RalphJSmit\Laravel\SEO\Facades\SEOManager;
use RalphJSmit\Laravel\SEO\Support\Tag;
use RalphJSmit\Laravel\SEO\TagCollection;
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
                // check if $tag extends Tag
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
                        try {
                            $item->attributes['inertia'] = '';
                        } catch (\Exception $e) {
                            dd($e, $item, $tag);
                        }
                    }
                }

                return $tag;
            });

            return $tags;
        });
    }
}
