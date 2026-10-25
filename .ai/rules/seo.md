---
paths:
  - 'app/Http/Controllers/Public/**'
  - 'app/Http/Controllers/PublicController.php'
  - 'resources/js/Pages/Public/**'
  - 'resources/js/Components/Public/**'
  - 'public/robots.txt'
  - 'resources/views/app.blade.php'
---

# Public SEO

Public pages must work without SSR. Search engines may use page content or the meta description
for snippets; neither metadata nor DOM order guarantees the displayed snippet.
`PublicSeoContractTest` checks server metadata, `PublicSeoMarkup.component.test.ts` checks heading
and snippet-exclusion wiring, and `PublicSeoRenderTest` checks metadata after browser mounting.

## Head: one owner, always complete
- Every public page-rendering action calls `PublicController::applyPageHead()`. Public pages never import
  Inertia's `<Head>` (ESLint blocks it): a client title replaces the server one after hydration,
  and Google indexes the replacement.
- **Title**: `<specific thing>` plus the derived ` - <tenant shortname>` suffix. Static page labels
  use translations without a hardcoded brand suffix; authored titles remain intact. Use `: ` for
  qualifiers rather than adding another ` - ` / ` | ` separator.
- **Description**: pass candidates best first, e.g. `[$model->description, __('seo.…_description', […])]`.
  `seoDescription()` strips HTML, decodes entities, prefers candidates of at least 50 characters
  and cuts at 160. These lengths are project heuristics, not Google requirements. Its final fallback
  names the page and tenant; homepages explicitly use the organisation description. Copy for the head
  lives in `lang/public/{lt,en}/seo.php`. Reuse a page's visible lead as its description, as the
  news archive, documents and meetings do.
- **Canonical**: the content owner's subdomain, plus only the query parameters that make a page
  distinct. `page` is kept by default; pass an explicit `canonicalUrl` from
  `getCanonicalUrl($tenant, ['page', 'tag'])` for the news archive. Parameters are sorted. Tracking
  parameters never enter it. If a model has `publicUrl()`, pass it as `canonicalUrl:` (institutions,
  calendar events); PKP institutions live on www.
- JSON-LD URLs use the canonical builders (`News::publicUrl()`, `Calendar::publicUrl()`,
  `Tenant::subdomain()`), never `url('/…')`, and breadcrumb crumbs use the owner's subdomain.
- WebSite schema uses the domain/subdomain root URL, without a language path, on both homepages.

## Rendered body
- Application-owned headings use one `<h1>` per page without skipped levels. Cards directly under it take
  `heading-tag="h2"` (`NewsCard`). A page with its own h1 calls `providePageHeading()`, which drops
  rich-content heroes to level 2. Hidden CMS titles and the homepage name remain an `sr-only` h1.
- Site chrome sits inside `data-nosnippet`: header, breadcrumbs, filter/sort toolbars, partner
  banners, consent and footer. Google honours the attribute only on `div`, `span` and `section`, so
  wrap a `<nav>`/`<header>`/`<footer>` in a `div` rather than putting it on them.

## Adding a public route
Add a case to `PublicSeoContractTest`'s dataset. Cover structure in component tests and use browser
tests for mounting, metadata stability and client-side navigation.

## robots.txt
`/uploads/media/` (media-library images, og:image) stays crawlable; the rest of `/uploads/` and
`/docs` do not. Staging is blocked separately by `BlockRobotsOnStagingDomains`.
