<!DOCTYPE html>
<html lang="{{ Lang::locale() }}" ng-app="vusa">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @include('layouts.user.icons')

    <title>{{ __('VU SA') }} | @yield('title')</title>

    <link href="{!! asset('css/app.css') !!}" rel="stylesheet" />

    @include('layouts.user.js')

    @yield('meta')

</head>

<body>

    {{-- <div class="fb-customerchat" id="fb-customerchat" page_id="134471024284"
        logged_in_greeting="Labas! Jeigu turi klausimų – kreipkis!"
        logged_out_greeting="Prisijunk, kad galėtumėme susirašyti per Facebook." theme_color="#BD2835"></div>

    <script>
        // Surašomi visi VU SA FB ID
        const fb_ids = {
            "vusa.lt": 134471024284,
            "evaf.vusa.lt": 66690509217,
            "gmc.vusa.lt": 175952889369,
            "if.vusa.lt": 155424121143535,
            "tf.vusa.lt": 1385960091616296,
            "mf.vusa.lt": 203356799735886,
            "tspmi.vusa.lt": 138081509575341,
            "kf.vusa.lt": 151229561194,
            "mif.vusa.lt": 255409734524049,
            "knf.vusa.lt": 100053813542158,
            "filf.vusa.lt": 426260814076167,
            "vm.vusa.lt": 155178824499611
            
        };

        const current_fb_id = fb_ids[window.location.hostname];

        // Ši funkcija pakeičia <div page_id>
        (function() {
            
            if (window.location.hostname !== "vusa.lt" && current_fb_id !== undefined) {
                document.getElementById("fb-customerchat").setAttribute("page_id", current_fb_id);
            }
        })();

        // Reikalinga FB Customer Chat paleidimui
        window.fbAsyncInit = function() {
            FB.init({
                appId: '912333495590130',
                autoLogAppEvents: true,
                xfbml: true,
                version: 'v2.11'
            });
        };
        (function(d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) return;
            js = d.createElement(s);
            js.id = id;
            js.src = "https://connect.facebook.net/en_US/sdk/xfbml.customerchat.js";
            fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));
    </script> --}}

    @if (Lang::locale() == 'en')
        <!--Start of Tawk.to Script-->
        <script type="text/javascript">
            var Tawk_API = Tawk_API || {},
                Tawk_LoadStart = new Date();
            (function() {
                var s1 = document.createElement("script"),
                    s0 = document.getElementsByTagName("script")[0];
                s1.async = true;
                s1.src = 'https://embed.tawk.to/5f71b135f0e7167d00145612/1foc6rga3';
                s1.charset = 'UTF-8';
                s1.setAttribute('crossorigin', '*');
                s0.parentNode.insertBefore(s1, s0);
            })();
        </script>
        <!--End of Tawk.to Script-->
    @else
        <!--Start of Tawk.to Script-->
        <script type="text/javascript">
            var Tawk_API = Tawk_API || {},
                Tawk_LoadStart = new Date();
            (function() {
                var s1 = document.createElement("script"),
                    s0 = document.getElementsByTagName("script")[0];
                s1.async = true;
                s1.src = 'https://embed.tawk.to/5f71b135f0e7167d00145612/default';
                s1.charset = 'UTF-8';
                s1.setAttribute('crossorigin', '*');
                s0.parentNode.insertBefore(s1, s0);
            })();
        </script>
        <!--End of Tawk.to Script-->
    @endif

    @include('layouts.user.navbar')

    <div class="flex-wrapper">

        @yield('content')
        @include('layouts.user.footer')

    </div>

    @include('cookieConsent::index')

</body>

</html>
