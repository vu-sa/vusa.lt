<?php

namespace App\Providers;

use App\Http\Middleware\TrimStrings;
use App\Services\ModelAuthorizer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use RalphJSmit\Laravel\SEO\Facades\SEOManager;
use RalphJSmit\Laravel\SEO\Support\Tag;
use RalphJSmit\Laravel\SEO\TagCollection;
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
        $this->app->singleton(ModelAuthorizer::class, function ($app) {
            return new ModelAuthorizer();
        });
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

        // HACK: Add inertia attribute to all SEO tags, so SPA can handle it
        SEOManager::tagTransformer(function (TagCollection $tags): TagCollection {

            // Apply the helper function to each tag in the collection
            $tags = $tags->map(function ($tag) {
                $this->addInertiaAttribute($tag);

                return $tag;
            });

            return $tags;
        });
    }

    private function addInertiaAttribute($tag)
    {
        if (is_subclass_of($tag, Tag::class)) {
            $tag->attributes['inertia'] = '';
        } elseif ($tag instanceof Collection) {
            foreach ($tag as $item) {
                $this->addInertiaAttribute($item);
            }
        }
    }
}
