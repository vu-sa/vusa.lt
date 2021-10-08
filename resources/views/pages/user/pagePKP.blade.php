@extends('layouts.user.master')

@section('title', $page['title'])

@section('content')
    <div class="container">
        <div class="pageTitle">{!! $page['title'] !!}</div>
        <?php $index = 0; $arraySize = sizeof($pkpTitle);?>
        @for($i = 0; $i < $arraySize; $i++)
            <?php $index += 1; ?>

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
            {!!($index < 2 & $index % 3 == 1) ? '<div class="row"> ':'' !!}
            <div class="col-lg-4 col-xs-12">
                <div class="pkpItem" style="background-color: {!! $color !!}">
                    <div class="pkpDescription">
                            <span>
                                {!! substr($pkpDescription[$i], 0, strpos($pkpDescription[$i], '<a ')) !!}
                                <br/>
                                @if(Lang::locale() == 'lt')
                                    <a class="pkpReadMore" href="/lt/{!! $pkpLinks[$i] !!}">Daugiau&nbsp;&nbsp;<span class="fa fa-caret-right"/></a>
                                @else
                                    <a class="pkpReadMore" href="/en/{!! $pkpLinks[$i] !!}">More&nbsp;&nbsp;<span class="fa fa-caret-right"/></a>
                                @endif
                            </span>
                    </div>
                    <div class="pkpTitle" align="center">
                        <h2>{!! $pkpTitle[$i] !!}</h2>
                    </div>
                </div>
            </div>

            {!!($index % 3 === 0) ? '</div> <div class="row" style="margin-top:40px"> ':'' !!}
            {!! ($index == sizeof($pkpTitle) ? '</div>':'') !!}
        @endfor
        <h3>Jei nori įkurti naują programą, klubą arba projektą - kreipkis <a href="mailto:pkp@vusa.lt">pkp@vusa.lt</a></h3>
    </div>
@endsection