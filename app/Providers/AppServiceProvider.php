<?php

namespace App\Providers;

use App\Http\Middleware\TrimStrings;
use App\Services\ModelAuthorizer;
use App\Services\PermissionService;
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
            return new ModelAuthorizer;
        });

        // Register our new permission service
        $this->app->singleton('permission.service', function ($app) {
            return new PermissionService($app->make(ModelAuthorizer::class));
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

        // Load translations from split directories (shared, admin, public)
        // Laravel will merge these with the default translations
        $this->loadSplitTranslations();

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

    /**
     * Load translations from the split directory structure.
     *
     * Translations are organized in:
     * - lang/shared/{locale}/ - Shared between admin and public
     * - lang/admin/{locale}/ - Admin-only translations
     * - lang/public/{locale}/ - Public-only translations
     *
     * All are loaded for Laravel backend (which needs all translations).
     * The Vite plugin handles splitting for frontend bundles.
     */
    private function loadSplitTranslations(): void
    {
        $langPath = lang_path();
        $directories = ['shared', 'admin', 'public'];

        // Get the file loader from the translator
        $loader = $this->app['translation.loader'];

        foreach ($directories as $dir) {
            $path = $langPath.'/'.$dir;

            if (is_dir($path)) {
                // Add this path as an additional translation path
                // The loader will look here for translations
                $loader->addPath($path);
            }
        }
    }
}
