<!DOCTYPE html>

{{-- TODO: Enable class="scroll-smooth" when Inertia scroll reset is fixed --}}
{{-- `data-surface="public"` switches the design-token scope in resources/css/app.css: the
     public site takes the editorial palette (warm paper / near-black, zero radius), admin keeps
     its own. Emitted server-side, from the same component check that gates @head and Umami
     below, so it is correct before the first paint. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark-mode-init"
    @if (str_starts_with($page['component'] ?? '', 'Public/')) data-surface="public" @endif>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    {{-- PWA Meta Tags --}}
    <link rel="manifest" href="/build/manifest.webmanifest">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="VU SA Mano">

    {{-- iOS Splash Screens --}}
    <link rel="apple-touch-startup-image" href="/images/pwa/splash-640x1136.png" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2)">
    <link rel="apple-touch-startup-image" href="/images/pwa/splash-750x1334.png" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2)">
    <link rel="apple-touch-startup-image" href="/images/pwa/splash-828x1792.png" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2)">
    <link rel="apple-touch-startup-image" href="/images/pwa/splash-1125x2436.png" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)">
    <link rel="apple-touch-startup-image" href="/images/pwa/splash-1170x2532.png" media="(device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3)">
    <link rel="apple-touch-startup-image" href="/images/pwa/splash-1179x2556.png" media="(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3)">
    <link rel="apple-touch-startup-image" href="/images/pwa/splash-1284x2778.png" media="(device-width: 428px) and (device-height: 926px) and (-webkit-device-pixel-ratio: 3)">
    <link rel="apple-touch-startup-image" href="/images/pwa/splash-1290x2796.png" media="(device-width: 430px) and (device-height: 932px) and (-webkit-device-pixel-ratio: 3)">
    <link rel="apple-touch-startup-image" href="/images/pwa/splash-1536x2048.png" media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2)">
    <link rel="apple-touch-startup-image" href="/images/pwa/splash-1668x2388.png" media="(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2)">
    <link rel="apple-touch-startup-image" href="/images/pwa/splash-2048x2732.png" media="(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2)">

    {{-- Public-page head metadata (title, description, canonical, Open Graph, hreflang, pagination
         links, …), set per-controller via PublicController::applyPageHead(). Admin pages set their
         own title client-side and render no server head. --}}
    @if (str_starts_with($page['component'] ?? '', 'Public/'))
        @head
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


    <meta id="theme-color" name="theme-color" content="#ffffff" />

    @include('meta-icons')

    {{-- Dark mode initialization script - MUST be before any CSS to prevent flash --}}
    <script>
        (function() {
            // Check localStorage for saved theme preference
            const savedTheme = localStorage.getItem('vueuse-color-scheme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            // Determine if dark mode should be active
            let isDark = false;

            if (savedTheme === 'dark') {
                isDark = true;
            } else if (savedTheme === 'light') {
                isDark = false;
            } else {
                // savedTheme is 'auto' or null - use system preference
                isDark = prefersDark;
            }

            if (isDark) {
                document.documentElement.classList.add('dark');
            }

            document.getElementById('theme-color').content = isDark ? '#252528' : '#ffffff';

            // Remove initialization class to allow normal operation
            document.documentElement.classList.remove('dark-mode-init');
        })();
    </script>

    {{-- MSAL v5 popup redirect bridge - must run before app boots --}}
    {{-- When MSAL loginPopup() redirects back, this broadcasts the auth code --}}
    {{-- via BroadcastChannel and closes the popup before the SPA loads --}}
    <script>
        (function() {
            var hash = window.location.hash;
            if (hash && hash.indexOf('state=') > -1) {
                try {
                    var params = new URLSearchParams(hash.substring(1));
                    var state = params.get('state');
                    if (state) {
                        var decoded = JSON.parse(atob(state));
                        if (decoded.id && decoded.meta && decoded.meta.interactionType === 'popup') {
                            var channel = new BroadcastChannel(decoded.id);
                            channel.postMessage({ v: 1, payload: hash });
                            channel.close();
                            window.close();
                            return;
                        }
                    }
                } catch(e) {}
            }
        })();
    </script>

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

    {{-- Umami analytics: cookieless, public pages only. Absent when unconfigured (staging, CI). --}}
    {{-- data-performance opts into Web Vitals (TTFB/FCP/LCP/CLS); it is off by default and --}}
    {{-- the figures cannot be backfilled, so it is collected from the start. --}}
    @if (config('services.umami.website_id') && str_starts_with($page['component'] ?? '', 'Public/'))
        <script defer src="{{ config('services.umami.script_url') }}"
                data-website-id="{{ config('services.umami.website_id') }}"
                data-performance="true"></script>
    @endif

    @inertiaHead

</head>

{{-- TODO: something injects margin-bottom of 8px --}}
{{-- The public surface paints from its own tokens; admin keeps the zinc canvas it has today
     until its own revamp. The font is set here rather than only on PublicLayout's root div
     because Reka teleports popovers, dialogs and dropdowns to <body> — outside that div, they
     otherwise fall back to the default sans instead of Atkinson. --}}
<body class="antialiased @if (str_starts_with($page['component'] ?? '', 'Public/')) font-public bg-background text-foreground @else font-sans bg-zinc-50 dark:bg-zinc-900 @endif" style="margin-bottom: 0px; padding-bottom: env(safe-area-inset-bottom, 0px);">
    @inertia
</body>

</html>
