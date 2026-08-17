---
paths:
  - config/vusa.php
---

# Config

## Where organisation values live: config vs Settings
Split by who changes the value:
- `config/vusa.php` — developer-facing constants that need a deploy (contact addresses, social profiles, company/VAT code, address). Shared to the frontend as the `organization` Inertia prop; read it there rather than hardcoding in a component.
- `app/Settings/*` (spatie/laravel-settings) — anything the board should change themselves. `SiteSettings::privacy_page_id` is the pattern: store a record **id**, resolve the URL server-side, and let the consumer hide the link when it is null.

The social URLs were consolidated from four disagreeing sets onto the public header buttons' values; if one of those turns out to be the stale account, `config/vusa.php` is the single place to fix it.
