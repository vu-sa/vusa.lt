<!DOCTYPE html>

{{-- TODO: Enable class="scroll-smooth" when Inertia scroll reset is fixed --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- <title>VU SA - {{ $title ?? "" }}</title> --}}
    <title inertia>{{ config('app.name', 'VU SA') }}</title>
    <meta name="og:title" content="VU SA | {{ $title ?? '' }}" />
    <meta name="description" content="{{ $description ?? '' }}">
    <meta name="og:description" content="{{ $description ?? '' }}">
    <meta name="image" content="{{ $image ?? '' }}">
    <meta name="og:image" content="{{ $image ?? '' }}">

    <!-- Fonts -->
    @googlefonts

    <!-- Styles -->
    @vite(['resources/css/app.css'])
    {{-- Naive UI must be after, because Tailwind has preflight styles which, otherwise, reset Naive UI --}}
    <meta name="naive-ui-style" />

    @routes

    @vite(['resources/js/app.js'])

    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
