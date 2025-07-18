<!DOCTYPE html>

{{-- TODO: Enable class="scroll-smooth" when Inertia scroll reset is fixed --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {!! isset($SEOData) ? seo($SEOData) : null !!}
    
    {{-- Hreflang tags for bilingual content --}}
    @if (isset($page['props']['seo']['hreflang']))
        @foreach ($page['props']['seo']['hreflang'] as $hreflangTag)
            {!! $hreflangTag !!}
        @endforeach
    @endif
    
    {{-- Site-wide structured data schemas --}}
    @if (isset($page['props']['schemas']))
        @foreach ($page['props']['schemas'] as $schema)
            {!! $schema->toScript() !!}
        @endforeach
    @endif
    
    {{-- Page-specific structured data schemas --}}
    @if (isset($JSONLD_Schemas) && is_array($JSONLD_Schemas))
        @foreach($JSONLD_Schemas as $schema)
            {!! $schema->toScript() !!}
        @endforeach
    @endif
            
        
    <meta name="theme-color" content="#252528" media="(prefers-color-scheme: dark)" />
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)" />

    @include('meta-icons')

    {{-- CSRF --}}
    {{-- <meta name="csrf-token" content="{{ csrf_token() }}"> --}}

    {{-- Atom Feed --}}
    @include('feed::links')

    {{-- Fonts --}}
    @googlefonts

    {{-- CSS and JS --}}
    @vite('resources/css/app.css')
    @vite('resources/js/app.ts')

    {{-- Ziggy Routes --}}
    @routes

    @inertiaHead

</head>

{{-- TODO: something injects margin-bottom of 8px --}}
<body class="font-sans antialiased bg-zinc-50 dark:bg-zinc-900" style="margin-bottom: 0px">
    @inertia
</body>

</html>
