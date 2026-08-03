<?php

/**
 * Static maintenance fallback, scp'd to storage/framework/maintenance.php by the deploy
 * workflow BEFORE anything else touches the server (see .github/workflows/deploy.yml).
 *
 * public/index.php requires storage/framework/maintenance.php — if present — before
 * requiring vendor/autoload.php, so this file must not depend on Composer, the framework,
 * or any app class. It exists to cover the brief window between the vendor.tar.gz upload
 * and the `git reset --hard` + vendor/ swap, where the checked-out app code and the
 * still-old vendor/ can briefly disagree — and as a safety net if a deploy fails at any
 * later point before `artisan up` restores normal service.
 *
 * `artisan down --render=errors::maintenance` (deployment:run's own `maintenance` step)
 * overwrites this file with Laravel's real maintenance-mode stub shortly after the vendor
 * swap, so this fallback only serves the handful of requests that land during that window.
 *
 * Content mirrors resources/views/errors/maintenance.blade.php — same copy, same inlined,
 * dependency-free styling (no @vite, no hashed asset URLs, since public/build may be
 * mid-swap too) — kept in sync manually since this file can't render a Blade view.
 */
http_response_code(503);
header('Retry-After: 60');
header('Refresh: 15');
header('Content-Type: text/html; charset=utf-8');

?><!DOCTYPE html>
<html lang="lt">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">

        <title>Tinklalapis atnaujinamas · Site under maintenance</title>

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
                <h1>Tinklalapis atnaujinamas</h1>
                <p class="lead">Šiuo metu atliekami techninės priežiūros darbai. Netrukus grįšime!</p>

                <p class="secondary" lang="en">
                    <strong>Site under maintenance</strong> —
                    We are performing scheduled maintenance and will be back shortly.
                </p>
            </div>
        </div>
    </body>
</html>
<?php
exit;
