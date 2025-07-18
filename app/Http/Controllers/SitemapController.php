<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapController extends Controller
{
    public function index(Request $request)
    {
        try {
            $tenant = $this->getTenantFromRequest($request);

            $cacheKey = "sitemap_index_{$tenant->id}";

            $xmlContent = Cache::tags(['sitemap', "tenant_{$tenant->id}"])
                ->remember($cacheKey, 3600, function () {
                    $sitemap = Sitemap::create();

                    // Add sitemap index entries
                    $sitemap->add(Url::create('/sitemap-pages.xml')
                        ->setLastModificationDate(now())
                        ->setPriority(0.8)
                    );

                    $sitemap->add(Url::create('/sitemap-news.xml')
                        ->setLastModificationDate(now())
                        ->setPriority(0.9)
                    );

                    $sitemap->add(Url::create('/sitemap-news-google.xml')
                        ->setLastModificationDate(now())
                        ->setPriority(0.9)
                    );

                    return $sitemap->render();
                });

            return response($xmlContent, 200)
                ->header('Content-Type', 'application/xml; charset=UTF-8');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            // Re-throw HTTP exceptions (like 404) without modification
            throw $e;
        } catch (\Exception $e) {
            Log::error('Sitemap index generation failed: '.$e->getMessage());

            return response('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>', 500)
                ->header('Content-Type', 'application/xml; charset=UTF-8');
        }
    }

    public function pages(Request $request)
    {
        $tenant = $this->getTenantFromRequest($request);

        $cacheKey = "sitemap_pages_{$tenant->id}";

        $xmlContent = Cache::tags(['sitemap', 'pages', "tenant_{$tenant->id}"])
            ->remember($cacheKey, 3600, function () use ($tenant) {
                $sitemap = Sitemap::create();

                // Add homepage
                $sitemap->add(Url::create('/')
                    ->setLastModificationDate(now())
                    ->setPriority(1.0)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                );

                // Add pages using model-based approach
                $pages = Page::where('tenant_id', $tenant->id)
                    ->where('is_active', true)
                    ->get();

                foreach ($pages as $page) {
                    $sitemap->add($page->toSitemapTag());
                }

                return $sitemap->render();
            });

        return response($xmlContent, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function news(Request $request)
    {
        $tenant = $this->getTenantFromRequest($request);

        $cacheKey = "sitemap_news_{$tenant->id}";

        $xmlContent = Cache::tags(['sitemap', 'news', "tenant_{$tenant->id}"])
            ->remember($cacheKey, 3600, function () use ($tenant) {
                $sitemap = Sitemap::create();

                // Add news archive page
                $sitemap->add(Url::create('/naujienos')
                    ->setLastModificationDate(now())
                    ->setPriority(0.8)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                );

                // Add individual news articles using model-based approach
                $news = News::where('tenant_id', $tenant->id)
                    ->where('draft', false)
                    ->orderBy('publish_time', 'desc')
                    ->take(1000) // Limit to recent articles
                    ->get();

                foreach ($news as $article) {
                    $sitemap->add($article->toSitemapTag());
                }

                return $sitemap->render();
            });

        return response($xmlContent, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function googleNews(Request $request)
    {
        $tenant = $this->getTenantFromRequest($request);

        $cacheKey = "sitemap_google_news_{$tenant->id}";

        $xmlContent = Cache::tags(['sitemap', 'news', "tenant_{$tenant->id}"])
            ->remember($cacheKey, 3600, function () use ($tenant) {
                // Google News articles should be from the last 2 days
                $cutoffDate = now()->subDays(2);

                // Add recent news articles for Google News
                $recentNews = News::where('tenant_id', $tenant->id)
                    ->where('draft', false)
                    ->where('publish_time', '>=', $cutoffDate)
                    ->orderBy('publish_time', 'desc')
                    ->take(1000)
                    ->get();

                // Create custom Google News sitemap XML
                $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
                $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'."\n";
                $xml .= '        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">'."\n";

                foreach ($recentNews as $article) {
                    $url = $article->lang === 'lt' ? '/naujiena/' : '/news/';
                    $url .= $article->permalink;
                    $fullUrl = url($url);

                    $xml .= "  <url>\n";
                    $xml .= '    <loc>'.htmlspecialchars($fullUrl)."</loc>\n";
                    $xml .= "    <news:news>\n";
                    $xml .= "      <news:publication>\n";
                    $xml .= '        <news:name>'.htmlspecialchars($tenant->shortname)."</news:name>\n";
                    $xml .= '        <news:language>'.htmlspecialchars($article->lang)."</news:language>\n";
                    $xml .= "      </news:publication>\n";
                    $xml .= '      <news:publication_date>'.$article->publish_time->toISOString()."</news:publication_date>\n";
                    $xml .= '      <news:title>'.htmlspecialchars($article->title)."</news:title>\n";
                    $xml .= "    </news:news>\n";
                    $xml .= "  </url>\n";
                }

                $xml .= '</urlset>';

                return $xml;
            });

        return response($xmlContent, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function getTenantFromRequest(Request $request): Tenant
    {
        // Try to get subdomain from route parameter first (for production)
        $subdomain = $request->route('subdomain');

        // If no route parameter, extract from HTTP_HOST header (for testing)
        if (! $subdomain) {
            $host = $request->header('host') ?? $request->getHost();
            $subdomain = explode('.', $host)[0] ?? 'www';
        }

        // Default to www if not set
        $subdomain = $subdomain ?? 'www';

        // Handle main domain
        if ($subdomain === 'www') {
            $subdomain = 'vusa';
        }

        $tenant = Tenant::where('alias', $subdomain)->first();

        if (! $tenant) {
            // Try to find by shortname as fallback
            $tenant = Tenant::where('shortname', $subdomain)->first();

            if (! $tenant) {
                abort(404, 'Tenant not found');
            }
        }

        return $tenant;
    }
}
