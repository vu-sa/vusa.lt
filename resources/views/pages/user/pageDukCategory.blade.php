@extends('layouts.user.master')

@if(Lang::locale() == 'lt')
    @section('title',"Dažniausiai užduodami klausimai")
@else
    @section('title',"Frequently asked questions")
@endif

@section('content')
    <div class="container" style="display: flex;justify-content: center;">
        <div class="col-lg-12 row" style="padding-bottom: 20px">
            @if(Lang::locale() == 'lt')
                <div class="pageTitle">Dažniausiai užduodami klausimai</div>
            @else
                <div class="pageTitle">Frequently asked questions</div>
            @endif

            <?php $index = 0; ?>
            @foreach($categories as $category)
                <?php $index += 1;?>
                {!!($index < 2 & $index % 3 == 1) ? '<div class="row"> ':'' !!}

                <?php
                $colorNumber = rand(1, 100);
                switch ($colorNumber % 3) {
                    case 1:
                        $color = '#bd2835';
                        break;
                    case 2:
                        $color = '#FBB03B';
                        break;
                    case 3:
                        $color = '#5D5D5D';
                        break;
                    default:
                        $color = '#5D5D5D';
                        break;
                }
                ?>

                <?php
                $link = str_replace(array('ė', 'Ė', 'ę', 'Ę'), 'e', strtolower($category));
                $link = str_replace(array('ą', 'Ą'), 'a', $link);
                $link = str_replace(array('ž', 'Ž'), 'z', $link);
                $link = str_replace(array('į', 'Į'), 'i', $link);
                $link = str_replace(array('č', 'Č'), 'c', $link);
                $link = str_replace(array('š', 'Š'), 's', $link);
                $link = str_replace(array('ų', 'Ų', 'ū', 'Ū'), 'u', $link);
                $link = str_replace(array('(', ')'), '', $link);
                $link = str_replace(array('.', ',', ':', '"', '„', '”', ' '), '', $link);
                //$link = str_replace(' ', '_', $link);
                ?>

                <a href="{{$title}}#{{$link}}">
                    <div class="col-lg-3" style="background-color: {{$color}}; color: white;  height: 245px; line-height: 245px;" align="center">
                        <h2 style="font-family: MYRIADPRO-BLACK; font-size: 20pt; display: inline-block; vertical-align: middle;">{{$category}}</h2>
                    </div>
                </a>

                {!!($index % 3 === 0) ? '':'<div class="col-lg-1"></div>' !!}

                {!!($index % 3 === 0) ? ' </div> <div class="row""> ':'' !!}
                {!! ($index == sizeof($categories) ? '</div>':'') !!}
            @endforeach
        </div>
    </div>
@endsection
