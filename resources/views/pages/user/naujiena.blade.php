@extends('layouts.user.master')

@section('title'){{ $topNews[0]->title }}@endsection

@section('meta')
    <meta property="og:url" content="{{ $url }}" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="VU SA | {{ $news->title }}" />
    <meta property="og:description" content="{!! strip_tags($news->short) !!}" />
    <meta property="og:image" content="{{ $news->image }}" />
    <meta name="description" content="{!! strip_tags($news->short) !!}" />
@endsection

@section('content')
    <div class="container">
        <div class="col-md-12">
            @include('pages.user.naujienaTemplate')
        </div>
    </div>

    <script type="text/javascript">
        var lang = '<?php echo Lang::locale(); ?>'

    </script>
@endsection
