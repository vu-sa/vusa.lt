# Language files

This directory contains some of the language files used in the application.

## Directory Structure

The translation files are organized into separate directories for optimized bundle sizes:

```
lang/
├── admin/          # Admin-only translations (loaded only in admin bundle)
│   ├── lt/         # Lithuanian PHP translation files
│   └── en/         # English PHP translation files
├── public/         # Public-only translations (loaded only in public bundle)
│   ├── lt/
│   └── en/
├── shared/         # Shared translations (loaded in both bundles)
│   ├── lt/
│   └── en/
├── lt.json         # Lithuanian JSON translations (shared)
├── en.json         # English JSON translations (shared)
└── php_*.json      # Generated files (gitignored)
```

### Adding new translations

- **Admin-only**: Place PHP files in `admin/lt/` and `admin/en/` (e.g., `tutorials.php`, `settings.php`)
- **Public-only**: Place PHP files in `public/lt/` and `public/en/` (e.g., `search.php`)
- **Shared**: Place PHP files in `shared/lt/` and `shared/en/` (e.g., `common.php`, `validation.php`)
- **JSON translations**: Add to `lt.json` and `en.json` (shared between both bundles)

The custom Vite plugin (`vite-plugins/i18n-split.ts`) compiles these into:
- `php_admin_{lang}.json` - shared + admin translations
- `php_public_{lang}.json` - shared + public translations

## Shortkeys

Shortkeys are available in directories `admin/`, `public/`, and `shared/`.

More on shorkeys: <https://laravel.com/docs/10.x/localization#using-short-keys>

## Translation strings

Sometimes it's not clear whether you should categorize localization strings to shortkeys. In that case,
temporary translation string usage is allowed.

More on translation strings: <https://laravel.com/docs/10.x/localization#using-translation-strings-as-keys>

## Other localization methods used in the application

These methods are also used in the application to *translate* strings.

### Localization imported directly to the components

These localization files are located in `resources/js/Constants/I18n/...`. They should consist more of non-repeating, longer strings.

~~The problem is that by default, all of the localization strings from Laravel are imported to the application. This means that guest users will have to download all of the localization strings, even if they are not using the application administration features.~~

**UPDATE**: This has been solved! The translation files are now split into admin/public/shared directories, and each bundle only loads the translations it needs. See the directory structure above.

### A simple `if` statement

This usage case should be phased out in the future.

#### Component.vue

```html
<template v-if="$page.props.app.locale === 'lt'">
Lietuviškas tekstas
</template>
<template v-else>
English text
</template>
```

### In database model translations

The models that have the trait `HasTranslations` can have translated attributes. This is a prefered way to translate model attributes.

More on model translations: <https://spatie.be/docs/laravel-translatable/v6/introduction>

### Different records for different languages

Some models have different records for different languages, e.g.  `Page` or `News` models. It should only be used when the content is completely different for different languages. In database model translations should be used instead.
