<!DOCTYPE html>

{{-- TODO: Enable class="scroll-smooth" when Inertia scroll reset is fixed --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {!! isset($SEOData) ? seo($SEOData) : null !!}
    
    @if (isset($JSONLD_Schemas) && is_array($JSONLD_Schemas))
        @foreach($JSONLD_Schemas as $schema)
            {!! $schema->toScript() !!}
        @endforeach
    @endif
            
        
    <meta name="theme-color" content="#252528" media="(prefers-color-scheme: dark)" />
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)" />

    @include('meta-icons')

    {{-- CSRF --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Atom Feed --}}
    @include('feed::links')

    {{-- Fonts --}}
    @googlefonts

    {{-- CSS and JS --}}
    @vite(['resources/js/app.ts'])

    {{-- Ziggy Routes --}}
    @routes

    @inertiaHead

</head>

{{-- TODO: something injects margin-bottom of 8px --}}
<body class="font-sans antialiased" style="margin-bottom: 0px">
    @inertia
</body>

</html>
