{{-- Google Analytics script --}}
@if (request()->getHttpHost() != 'naujas.vusa.lt')
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-9943804-1"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'UA-9943804-1');

    </script>

    {{-- Clarity (MS) script --}}

    <script type="text/javascript">
        (function(c, l, a, r, i, t, y) {
            c[a] = c[a] || function() {
                (c[a].q = c[a].q || []).push(arguments)
            };
            t = l.createElement(r);
            t.async = 1;
            t.src = "https://www.clarity.ms/tag/" + i;
            y = l.getElementsByTagName(r)[0];
            y.parentNode.insertBefore(t, y);
        })(window, document, "clarity", "script", "55jsmsu47t");

    </script>
@endif

<script type="text/javascript" src="{!!  asset('js/app.js') !!}"></script>
