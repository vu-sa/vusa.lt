<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>VU SA · {{ __('activity_requests.unavailable_title') }}</title>
    @vite('resources/css/app.css')
</head>
<body data-surface="public" class="min-h-screen bg-background text-foreground">
    <main class="mx-auto max-w-xl px-5 py-16">
        <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Mano VU SA</p>
        <h1 class="mt-3 text-2xl font-bold">{{ __('activity_requests.unavailable_title') }}</h1>
        <p class="mt-4 text-muted-foreground">{{ __('activity_requests.unavailable_body') }}</p>
        <a href="{{ route('dashboard') }}" class="mt-6 inline-flex min-h-11 items-center text-brand">{{ __('activity_requests.open_mano') }}</a>
    </main>
</body>
</html>
