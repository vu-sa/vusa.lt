@extends('layouts.user.master')

@if(Lang::locale() == 'lt')
    @section('title', 'Pradinis')
@else
    @section('title', 'Main')
@endif

@section('content')

    <div class="blackBackground">
        <div id="newsSlides" class="carousel slide" data-ride="carousel">
            @if(sizeof($topNews) > 0)
                <ol class="carousel-indicators">
                    @for($i = 0; $i < sizeof($topNews); $i++)
                        @if ($i == 0)
                            <li data-target="#newsSlides" data-slide-to="0" class="active"></li>
                        @else
                            <li data-target="#newsSlides" data-slide-to="{{$i}}"></li>
                        @endif
                    @endfor
                </ol>
            @endif
            <div class="carousel-inner" role="listbox">
                <?php $index = -1;?>
                @foreach($topNews as $news)
                    <?php
                    if ($index == -1) {
                        $class = 'item active';
                        $index = 0;
                    } else {
                        $class = 'item';
                    }
                    ?>
                    <div class="carousel-item {{$class}}">
                        @if(Lang::locale() == 'lt')
                            <?php $newsLink = $navbarRoot . '/lt/naujiena/' . $news['permalink'];?>
                        @else
                            <?php $newsLink = $navbarRoot . '/en/news/' . $news['permalink'];?>
                        @endif

                        <div class="layer">
                            <?php
                            if (strtotime($news['publish_time']) < strtotime('2016-08-02')) {
                                $imageLocation = 'images/icons/naujienu_foto.png';
                            } else {
                                $imageLocation = $news['image'];
                            } 
                            ?>
                            <a class="newsSlideLink" href="{!! asset($newsLink) !!}">
                                <img class="carousel-img" src="{!! asset($imageLocation) !!}"/>
                            </a>
                        </div>
                        <a class="newsSlideLink" href="{!! asset($newsLink) !!}">
                            <div class="carousel-caption">
                                <h1>{{ $news['title'] }}</h1>
                                <p style="line-height: 1.2">{!! $news['short']  !!}</p>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

@if(Lang::locale() == 'lt')
    <div class="container">
        <div style="margin-top: 30px">
            <?php $index = 0;?>
            @foreach($mainPageInfo as $mainPageInfoItem)
                <?php $index += 1;?>
                {!!($index < 2 & $index % 3 == 1) ? '<div class="row"> ':'' !!}

                @if($mainPageInfoItem['position'] == 'naujiena')
                    <?php
                    if (strtotime($mainPageInfoItem['newsInfo']['publish_time']) < strtotime('2016-08-02')) {
                        $imageLocation = 'images/icons/naujienu_foto.png';
                    } else {
                        $imageLocation = $mainPageInfoItem['newsInfo']['image'];
                    } ?>
                    <div class="col-md-4">
                        <a class="newsItemLink" href="{{Lang::locale() == 'lt' ? '/lt/naujiena' :'/en/news'}}/{!! $mainPageInfoItem['newsInfo']['permalink'] !!}">
                            <div class="newsShadow">
                                <div class="mainNaujiena cat{{$mainPageInfoItem['newsInfo']['cat']}}">
                                    <img class="newsImage" src="{!! $imageLocation  !!}">

                                    <div class="newsTextMainPage">
                                        <span class="title"> {!! $mainPageInfoItem['newsInfo']['title'] !!} </span>
                                        <br> <br>
                                        <span>
                                            <?php
                                            $words = explode(" ", strip_tags($mainPageInfoItem['newsInfo']['short']));
                                            $shortInfo = '';
                                            for ($i = 0; $i < sizeof($words); $i++) {
                                                if (strlen($shortInfo) - 1 > 120)
                                                    break;
                                                $shortInfo .= $words[$i] . " ";
                                            }
                                            ?>
                                            {!! substr($shortInfo, 0, -1) !!}...
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif
                @if($mainPageInfoItem['position'] == 'modulis')
                    <?php $name = 'pages.user.' . $mainPageInfoItem['moduleName'];?>
                    @include($name)
                @endif

                @if($mainPageInfoItem['position'] == 'infoPage')
                    <?php
                    if (strpos($mainPageInfoItem['image'], 'vusa.lt') !== false) {
                        $imageLocation = $mainPageInfoItem['image'];
                    } else {
                        $imageLocation = 'uploads/news/' . $mainPageInfoItem['image'];
                    }
                    ?>
                    <div class="col-lg-4 col-sm-6">
                        <a target="_blank" href="/{{Lang::locale()}}{{$mainPageInfoItem['link']}}">
                            <div class="newsFoto newsFotoBorder">
                                <img src="{!! $mainPageInfoItem['image'] !!}">
                            </div>
                        </a>
                    </div>
                @endif
                {!!($index % 3 === 0) ? '</div> <div class="row"> ':'' !!}
                {!! ($index == sizeof($mainPageInfo) ? '</div>':'') !!}

            @endforeach
        </div>
        <div style="text-align: center">
            <a href="https://www.vu.lt/parduotuve/" target="_blank">
            <img style="width: 70%; margin-bottom: 2em" src="/images/photos/atributika_banner.jpg" />
        </a>
        </div>
    </div>

@else
<br>
<br>
<div class="container">
    <div class="row">
    <div class="col-lg-4 col-sm-6">
        <a target="_blank" href="/en/academic-leave-deferment-of-studies-and-study-resumption">
            <div class="newsFoto newsFotoBorder">
                <img src="https://vusa.lt/uploads/ikonos/ai-01-01.jpg">
            </div>
        </a>
    </div>
    <div class="col-lg-4 col-sm-6">
        <a target="_blank" href="/en/contacts/central-office">
            <div class="newsFoto newsFotoBorder">
                <img src="https://vusa.lt/uploads/ikonos/c-01-01.jpg">
            </div>
        </a>
    </div>
    <div class="col-lg-4 col-sm-6">
        <a target="_blank" href="/en/incentive-scholarships">
            <div class="newsFoto newsFotoBorder">
                <img src="https://vusa.lt/uploads/ikonos/fs-01.jpg">
            </div>
        </a>
    </div>
</div>
</div>

@endif
    <script>
    events = <?php echo json_encode($events);?>;
    currentYearMonth = <?php echo $currentYearMonth;?>;

    $(document).tooltip({
            selector: '.eventDay',
            html: true,
            template: '<div class="tooltip" role="tooltip"><div class="tooltip-inner text-left"></div></div>'
        });
    
    </script>
@endsection
