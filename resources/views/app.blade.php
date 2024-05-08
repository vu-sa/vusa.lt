<!DOCTYPE html>

{{-- TODO: Enable class="scroll-smooth" when Inertia scroll reset is fixed --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ $title ?? 'VU SA' }} - VU SA</title>
    <meta property="og:title" content="{{ $title ?? 'vusa.lt' }} - VU SA" />
    <meta name="description" content="{{ $description ?? '' }}">
    <meta property="og:description" content="{{ $description ?? '' }}">
    <meta name="image" content="{{ $image ?? '' }}">
    <meta property="og:image" content="{{ $image ?? '' }}">
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
