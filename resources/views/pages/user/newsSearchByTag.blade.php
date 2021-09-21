@extends('layouts.user.master')

@if(Lang::locale() == 'lt')
    @section('title', 'Naujienų paieška pagal žymę')
@else
    @section('title', 'News search by tag')
@endif


@section('content')
    <div class="container" id="infoPage">
        @if(Lang::locale() == 'lt')
            <div class="pageTitle">Naujienų paieška pagal žymę</div>
        @else
            <div class="pageTitle">News search by tag</div>
        @endif

        @if( sizeof($news) > 0)
            @foreach($news as $new)
                <?php
                if (strtotime($new['publish_time']) < strtotime('2016-08-02')) {
                    $imageLocation = '/images/icons/naujienu_foto.png';
                } else {
                    $imageLocation = $new['image'];
                }
                ?>
                <div class="newsArchive">
                    <div>
                        <img src="{{ $imageLocation }}" width="230px">
                    </div>
                    <div class="newsArchiveInfo">
                        @if(Lang::locale() == 'lt')
                            <a class="newsArchiveTitle" href="/lt/naujiena/{{ $new->permalink }}">{{$new->title}}</a><br/>
                        @else
                            <a class="newsArchiveTitle" href="/en/news/{{ $new->permalink }}">{{$new->title}}</a><br/>
                        @endif

                        <?php $date = explode(" ", strip_tags($new->publish_time)); ?>
                        @if(Lang::locale() == 'lt')
                            {{ $date[0] }} &nbsp;&nbsp;&nbsp;<b>Šaltinis:</b> {{$new->source}}<br/>
                        @else
                            {{ $date[0] }} &nbsp;&nbsp;&nbsp;<b>Source:</b> {{$new->source}}<br/>
                        @endif

                        {!! substr(strip_tags($new->short), 0, 260).'...' !!}<br/>
                        @if(strlen(substr(strip_tags($new->short), 0, 260)) <  140)
                            <br/>
                        @endif
                        @if($new['tags'] != null)
                            <?php $tags = explode(';', $new->tags);?>
                            @if(Lang::locale() == 'lt')
                                @for($y = 0; $y < sizeof($tags); $y++)
                                    <a href="/lt/naujiena/zyme/{{$tags[$y]}}">
                                        <span style="background-color: #919191" class="newsTag {{$new->cat}}">{{$tags[$y]}}</span>
                                    </a>
                                @endfor
                            @else
                                @for($y = 0; $y < sizeof($tags); $y++)
                                    <a href="/en/news/tag/{{$tags[$y]}}">
                                        <span style="background-color: #919191" class="newsTag {{$new->cat}}">{{$tags[$y]}}</span>
                                    </a>
                                @endfor
                            @endif

                        @endif
                    </div>
                </div>
                <br/>
            @endforeach
            {!! $news !!}
        @else
            @if(Lang::locale() == 'lt')
                <h1>Tokios žymės nėra. </h1>
            @else
                <h1>Tag not found.</h1>
            @endif
            <br/><br/><br/><br/><br/><br/><br/>
        @endif
    </div>

    <script>
        $("span.newsTag").hover(function () {
            if ($(this).hasClass('1')) {
                $(this).css("background-color", "#BD2835");
            }
            if ($(this).hasClass('2')) {
                $(this).css("background-color", "#FBB03B");
            }
        }, function () {
            $(this).css("background-color", "#919191");
        });
    </script>
@endsection