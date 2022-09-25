<!DOCTYPE html>

{{-- TODO: Enable class="scroll-smooth" when Inertia scroll reset is fixed --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'VU SA') }}</title>
    <meta name="og:title" content="VU SA | {{ $title ?? '' }}" />
    <meta name="description" content="{{ $description ?? '' }}">
    <meta name="og:description" content="{{ $description ?? '' }}">
    <meta name="image" content="{{ $image ?? '' }}">
    <meta name="og:image" content="{{ $image ?? '' }}">
    <meta name="color-scheme" content="light dark">
    {{-- <meta name="theme-color" content="#bd2835" /> --}}

    {{-- Atom Feed --}}
    @include('feed::links')

    {{-- Fonts --}}
    @googlefonts

    <!-- Styles -->
    @vite(['resources/css/app.css'])

    {{-- Naive UI must be after, because Tailwind has preflight styles which, otherwise, reset Naive UI --}}
    <meta name="naive-ui-style" />

    {{-- Ziggy Routes --}}
    @routes

    @vite(['resources/js/app.ts'])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
