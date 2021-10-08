<!DOCTYPE html>
<html lang="{{ Lang::locale() }}" ng-app="vusa">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>VU SA administravimas</title>

    <link href="{!!  asset('css/admin.css') !!}" rel="stylesheet" />
    <script type="text/javascript" src="{!!  asset('js/admin.js') !!}"></script>

    @yield('meta')

</head>

<body class="hold-transition skin-red sidebar-mini">
    <div class="wrapper">
        @include('layouts.admin.header')

        @include('layouts.admin.leftNavbar')

        @yield('content')
    </div>

</body>

</html>
