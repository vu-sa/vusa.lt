<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title')</title>

        <style>
            body {
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            }
        </style>
    <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="antialiased">
        <div class="flex justify-center min-h-screen bg-zinc-50 items-center sm:pt-0">
            <div class=" max-w-xl mx-auto px-4 sm:px-6 lg:px-8 gap-8">
                <div class="flex flex-col items-center gap-8">
                    <img class="w-64" src="/images/photos/vezlys-zygiuoja-transparent.png">
                    <div class="tracking-wide flex flex-col items-center">
                        <p class="text-5xl font-extrabold">
                            @yield('code')
                        </p>
                        <p class="text-center text-xl">@yield('message')</p>
                        <p class="text-center mt-2 text-zinc-400 italic text-sm">@yield('extended-message').</p>
                        <a class="underline mt-8 hover:text-red-600 transition text-xl" href="{{ config('app.url') }}">← Grįžti į vusa.lt</a>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
