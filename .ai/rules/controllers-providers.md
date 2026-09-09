---
paths:
  - 'app/Http/Controllers/Public/**,app/Http/Controllers/PublicController.php,app/Providers/AppServiceProvider.php'
---

# Controllers Providers

## Public-page <head> metadata goes through PublicController::applyPageHead()
Migrated from ralphjsmit/laravel-seo to first-party laravel/head (2026-08-14). Every Public/*Controller calls PublicController::applyPageHead() instead of the old shareAndReturnSEOObject(). Title suffixes are centralized there (derives " - <tenant>" automatically) — never hand-concatenate a suffix at a call site, pass titleSuffix: '...' to override or '' to suppress. Site-wide defaults live in Head::defaults() (AppServiceProvider::boot()). resources/views/app.blade.php renders @head (guarded to Public/* components) before @inertiaHead; resources/js/public.ts sets serverHead: true — no client-side <Head> anywhere in PublicLayout.vue. Admin (/mano) deliberately untouched, still uses client-side <Head :title> in AdminLayout.vue. JSON-LD stays on spatie/schema-org, not migrated to Head's schema builders.
