---
paths:
  - 'app/Http/Controllers/Public/**'
  - 'app/Http/Controllers/PublicController.php'
  - 'app/Providers/AppServiceProvider.php'
---

# Public page head

## Public-page <head> metadata goes through PublicController::applyPageHead()
Uses first-party laravel/head. Every Public/*Controller calls PublicController::applyPageHead(). Title suffixes are centralized there (derives " - <tenant>" automatically) — never hand-concatenate a suffix at a call site, pass titleSuffix: '...' to override or '' to suppress. Site-wide defaults live in Head::defaults() (AppServiceProvider::boot()). resources/views/app.blade.php renders @head (guarded to Public/* components) before @inertiaHead; resources/js/public.ts sets serverHead: true — no client-side <Head> anywhere in PublicLayout.vue. Admin (/mano) deliberately untouched, still uses client-side <Head :title> in AdminLayout.vue. JSON-LD stays on spatie/schema-org, not migrated to Head's schema builders.
