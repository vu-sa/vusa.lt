{{--
    Standalone maintenance page.

    Deliberately does NOT extend errors::minimal and does NOT use @vite: this view is
    prerendered into storage/framework/maintenance.php by `artisan down --render`, and the
    deployment swaps out both public/build and vendor/ while the site is down. A hashed
    stylesheet URL would 404 mid-deploy, so all styling is inlined here.

    Rendered without a request (and before SetLocale runs), so the locale cannot be resolved
    per visitor — both languages are shown, with the locale passed explicitly to __().
--}}
<!DOCTYPE html>
<html lang="lt">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">

        <title>{{ __('Site under maintenance', [], 'lt') }} · {{ __('Site under maintenance', [], 'en') }}</title>

        <style>
            :root {
                --background: #fafafa;
                --heading: #27272a;
                --body: #52525b;
                --muted: #a1a1aa;
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    --background: #18181b;
                    --heading: #f4f4f5;
                    --body: #d4d4d8;
                    --muted: #71717a;
                }
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem 1.5rem;
                background-color: var(--background);
                color: var(--body);
                font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                -webkit-font-smoothing: antialiased;
                letter-spacing: 0.015em;
            }

            .card {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 2rem;
                max-width: 36rem;
                text-align: center;
            }

            .turtle {
                width: 16rem;
                max-width: 100%;
                height: auto;
            }

            h1 {
                margin: 0 0 0.75rem;
                font-size: 1.875rem;
                line-height: 1.2;
                font-weight: 800;
                color: var(--heading);
            }

            .lead {
                margin: 0;
                font-size: 1.125rem;
                line-height: 1.6;
            }

            .secondary {
                margin: 1.75rem 0 0;
                font-size: 0.875rem;
                line-height: 1.6;
                color: var(--muted);
            }

            .secondary strong {
                font-weight: 600;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <img class="turtle" src="/images/photos/vezlys-zygiuoja-transparent.png" alt="">

            <div>
                <h1>{{ __('Site under maintenance', [], 'lt') }}</h1>
                <p class="lead">{{ __('We are performing scheduled maintenance and will be back shortly.', [], 'lt') }}</p>

                <p class="secondary" lang="en">
                    <strong>{{ __('Site under maintenance', [], 'en') }}</strong> —
                    {{ __('We are performing scheduled maintenance and will be back shortly.', [], 'en') }}
                </p>
            </div>
        </div>
    </body>
</html>
