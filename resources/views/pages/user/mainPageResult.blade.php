@extends('layouts.user.master')

@if(Lang::locale() == 'lt')
    @section('title', 'Paieška')
@else
    @section('title', 'Search')
@endif


@section('content')
    <div class="container" id="infoPage">
        @if(Lang::locale() == 'lt')
            <div class="pageTitle">Paieška</div>
        @else
            <div class="pageTitle">Search</div>
        @endif

        @if( sizeof($searchRezNews) > 0 || sizeof($searchRezPages) > 0)
            @if(Lang::locale() == 'lt')
                <h4>Rezultatai informaciniuose puslapiuose: </h4>
            @else
                <h4>Search results in pages: </h4>
            @endif

            <br/>
            @if( sizeof($searchRezPages) > 0)
                @foreach($searchRezPages as $page)
                    <div class="newsArchive">
                        {{--<div>
                            <img src="{{ $imageLocation }}" width="230px">
                        </div>--}}
                        <div class="newsArchiveInfo">
                            <a class="newsArchiveTitle" href="/{{Lang::locale()}}/{{ $page->permalink }}">{{$page->title}}</a><br/>
                        </div>
                    </div>
                    <br/>
                @endforeach
            @else
                —
            @endif
            @if(Lang::locale() == 'lt')
                <h4>Rezultatai naujienose: </h4>
            @else
                <h4>Search results in news: </h4>
            @endif

            <br/>
            @if( sizeof($searchRezNews) > 0)
                @foreach($searchRezNews as $new)
                    <?php
                    if (strtotime($new['publish_time']) < strtotime('2016-08-02')) {
                        $imageLocation = '/images/icons/naujienu_foto.png';
                    } elseif (strpos($new['image'], 'vusa.lt') !== false) {
                        $imageLocation = $new['image'];
                    } else {
                        $imageLocation = 'uploads/news/' . $new['image'];
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
                            {{ $date[0] }} &nbsp;&nbsp;&nbsp;<b>Šaltinis:</b> {{$new->source}}<br/>

                            {!! substr(strip_tags($new->short), 0, 260).'...' !!}<br/>
                            @if(strlen(substr(strip_tags($new->short), 0, 260)) <  140)
                                <br/>
                            @endif
                            @if($new['tags'] != null)
                                <?php $tags = explode(';', $new->tags);?>
                                @if(Lang::locale() == 'lt')
                                    @for($y = 0; $y < sizeof($tags); $y++)
                                        <a href="/lt/naujiena/zyme/{{$tags[$y]}}">
                                            <span style="background-color: #919191" class="newsTag {{$new->cat}}">#{{$tags[$y]}}</span>
                                        </a>
                                    @endfor
                                @else
                                    @for($y = 0; $y < sizeof($tags); $y++)
                                        <a href="/en/news/tag/{{$tags[$y]}}">
                                            <span style="background-color: #919191" class="newsTag {{$new->cat}}">#{{$tags[$y]}}</span>
                                        </a>
                                    @endfor
                                @endif
                            @endif
                        </div>
                    </div>
                    <br/>
                @endforeach
            @else
                —
            @endif
        @else
            @if(Lang::locale() == 'lt')
                <h2>Paieškos rezultatų nėra.</h2>
            @else
                <h2>No result.</h2>
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