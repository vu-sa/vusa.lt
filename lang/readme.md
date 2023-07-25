# Language files

This directory contains some of the language files used in the application.

## Shortkeys

Shortkeys are available in directories `en/...` and `lt/...`.

More on shorkeys: <https://laravel.com/docs/10.x/localization#using-short-keys>

## Translation strings

Sometimes it's not clear whether you should categorize localization strings to shortkeys. In that case,
temporary translation string usage is allowed.

More on translation strings: <https://laravel.com/docs/10.x/localization#using-translation-strings-as-keys>

## Other localization methods used in the application

These methods are also used in the application to *translate* strings.

### Localization imported directly to the components

These localization files are located in `resources/js/Constants/I18n/...`. They should consist more of non-repeating, longer strings.

The problem is that by default, all of the localization strings from Laravel are imported to the application. This means that guest users will have to download all of the localization strings, even if they are not using the application administration features.

Of course, some localization fetching methods could be implemented as it's shown [here](https://github.com/xiCO2k/laravel-vue-i18n#with-vite), but it needs a better strategy.

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
