---
paths:
  - 'app/Http/Controllers/SitemapController.php,app/Models/News.php'
---

# Controllers Models

## Sitemap news loops must exclude blank permalinks
Laravel's RouteUrlGenerator treats an empty-string route parameter the same as a missing one (`$parameters[$m[1]] !== ''` check in replaceNamedParameters), so LocalizedRouteSlugs::route('news', ['news' => '']) throws UrlGenerationException instead of producing a blank segment. News.permalink is nullable and not always backfilled on legacy rows (found News#19, tenant 16, permalink=''). SitemapController::news()/googleNews() now filter `->where('permalink', '!=', '')` (also excludes NULL) before building sitemap URLs — keep that filter if you touch these queries, and add the same guard anywhere else that builds a 'news' route URL from a News record without validating permalink first.
