<?php

namespace App\Providers;

use App\Services\ModelAuthorizer;
use App\Services\PermissionService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Head\Enums\OgType;
use Laravel\Head\Enums\TwitterCard;
use Laravel\Head\Facades\Head;
use Laravel\Head\HeadBuilder;
use Spatie\Translatable\Facades\Translatable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    #[\Override]
    public function register()
    {
        $this->app->singleton(ModelAuthorizer::class, fn ($app) => new ModelAuthorizer);

        // Register our new permission service
        $this->app->singleton('permission.service', fn ($app) => new PermissionService($app->make(ModelAuthorizer::class)));
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(125);

        $this->configureRateLimiting();

        // Load translations from split directories (shared, admin, public)
        // Laravel will merge these with the default translations
        $this->loadSplitTranslations();

        // Needed for json_content in Content model
        TrimStrings::skipWhen(fn (Request $request) => $request->is('mano/*'));

        Translatable::fallback(
            fallbackLocale: 'lt'
        );

        // Site-wide public head defaults. Page-specific values (title, description, canonical,
        // hreflang, etc.) are set at runtime in PublicController::applyPageHead().
        Head::defaults(fn (HeadBuilder $head) => $head
            ->description('VU SA - visuomeninė, ne pelno siekianti, nepolitinė, ekspertinė švietimo organizacija, atstovaujanti studentų interesams Vilniaus universitete bei už jo ribų')
            ->og(siteName: 'VU SA', type: OgType::Website)
            ->ogImage(config('app.url').'/images/photos/vusa.jpg')
            ->twitter(card: TwitterCard::SummaryWithLargeImage)
            ->meta('author', 'VU SA')
            ->robots('max-snippet:-1,max-image-preview:large,max-video-preview:-1')
            ->link('sitemap', '/sitemap.xml', ['type' => 'application/xml'])
            ->preconnect('https://embed.tawk.to', crossorigin: 'anonymous')
            ->preload('https://cdn.userway.org/widgetapp/images/body_wh.svg', as: 'image'));
    }

    /**
     * Configure the rate limiters for the application.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip()));

        RateLimiter::for('summerCamps', fn (Request $request) => $request->user()
            ? Limit::perMinute(100)->by($request->user()->id)
            : Limit::perMinute(15)->by($request->ip()));

        RateLimiter::for('formRegistrations', fn (Request $request) => $request->user()
            ? Limit::perMinute(100)->by($request->user()->id)
            : Limit::perHour(5)->by($request->ip()));

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(5)->by($email.$request->ip());
        });

        RateLimiter::for('textBoxSubmissions', fn (Request $request) => $request->user()
            ? Limit::perMinute(10)->by($request->user()->id)
            : Limit::perMinute(5)->by($request->ip()));
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
