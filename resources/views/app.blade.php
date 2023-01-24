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

<body class="font-sans antialiased">
    @inertia
</body>

</html>
