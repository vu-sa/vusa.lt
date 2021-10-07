@extends('layouts.user.master')

@if(Lang::locale() == 'lt')
    @section('title', 'Naujienų archyvas')
@else
    @section('title', 'News archive')
@endif

@section('content')

    <div class="container" id="infoPage">
        @if(Lang::locale() == 'lt')
            <div class="pageTitle">Naujienų archyvas</div>
        @else
            <div class="pageTitle">News archive</div>
        @endif

        <div class="col-md-9" style="padding-left: 0">
            {{ Form::open(['class'=>'form-inline']) }}
            @if(Lang::locale() == 'lt')
                <div class="form-group">
                    {{ Form::label('searchText', 'Paieška') }}
                    &nbsp;
                    {{ Form::text('searchText', $searchKeywords[1] ?? "", array('class'=>'form-control', 'placeholder'=>'Įvesk pavadinimą ar jo dalį', 'size'=>'35')) }}
                </div>
                {{--<div class="form-group">
                    {!! Form::label('dateFrom', ' ') !!}
                    {{ Form::date('dateFrom', $searchKeywords[2], array('class'=>'form-control', 'placeholder'=>'Data nuo')) }}
                </div>
                <div class="form-group">
                    {!! Form::label('dateTo', ' ') !!}
                    {{ Form::date('dateTo', $searchKeywords[3], array('class'=>'form-control', 'placeholder'=>'Data iki')) }}
                </div>--}}
                &nbsp;
                {{ Form::submit('Ieškoti', ['class'=>'btn btn-dark']) }}
            @else
                <div class="form-group">
                    {{ Form::label('searchText', 'Search') }}
                    {{ Form::text('searchText', $searchKeywords[1] ?? "", array('class'=>'form-control', 'placeholder'=>'Write title or part of it', 'size'=>'35')) }}
                </div>
                {{ Form::submit('Search', ['class'=>'btn btn-default']) }}
            @endif
            {{ Form::close() }}
        </div>
        <div style="height: 80px"></div>
        @if( $_SERVER['REQUEST_METHOD'] === 'GET' || $size > 0)
            <div>
                @foreach($news as $new)
                    <?php
                    if (strtotime($new['publish_time']) < strtotime('2016-08-02')) {
                        $imageLocation = '/images/icons/naujienu_foto.png';
                    } else {
                        $imageLocation = $new['image'];
                    } ?>
                    <div class="newsArchive">
                        <div style="padding-right: 10px">
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
                            <br/>
                            {!! substr(strip_tags($new->short), 0, 261).'...' !!}<br/>
                            @if(strlen(substr(strip_tags($new->short), 0, 260)) <  140)
                                <br/>
                            @endif
                            @if($new['tags'] != null)
                                <?php $tags = explode(';', $new->tags);?>
                                @if(Lang::locale() == 'lt')
                                    @for($y = 0; $y < sizeof($tags); $y++)
                                        <a href="/lt/naujiena/zyme/{{$tags[$y]}}">
                                        <span style="background-color: rgb(180, 180, 180);  align:center;"
                                              class="newsTag {{$new->cat}}">&nbsp;{{$tags[$y]}}</span>
                                        </a>
                                    @endfor
                                @else
                                    @for($y = 0; $y < sizeof($tags); $y++)
                                        <a href="/en/news/tag/{{$tags[$y]}}">
                                        <span style="background-color: rgb(180, 180, 180);  align:center;"
                                              class="newsTag {{$new->cat}}">&nbsp;{{$tags[$y]}}</span>
                                        </a>
                                    @endfor
                                @endif
                            @endif
                        </div>
                    </div>
                    <br/>
                @endforeach
            </div>
            {!! $news !!}
        @else
            <br/>
            @if(Lang::locale() == 'lt')
                <h2>Paieškos rezultatų nerasta</h2>
            @else
                <h2>No search results</h2>
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
            if ($(this).hasClass('3')) {
                $(this).css("background-color", "##919191");
            }
        }, function () {
            $(this).css("background-color", "#B4B4B4");
        });

        $('#dateFrom').datepicker();
        $('#dateTo').datepicker();
    </script>
@endsection