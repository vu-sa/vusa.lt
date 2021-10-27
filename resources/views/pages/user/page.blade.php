@extends('layouts.user.master')

@section('title'){{$page['title']}}@endsection

@section('meta')
    <meta property="og:url" content="{{"http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']}}"/>
    <meta property="og:type" content="website"/>
    <meta property="og:title" content="VU SA | {{$page['title']}}"/>
    <meta property="og:description" content=""/>
    <meta property="og:image" content="/images/icons/logos/vusa.lin.hor.png"/>
@endsection

@section('content')
    <div class="container" id="infoPage">
        <div class="pageTitle">{{$page['title']}}</div>

        @if (in_array(request()->getHttpHost(), ["vusa.lt", "naujas.vusa.lt", "vusa.testas"]) == false)
            @if (Lang::locale() == 'lt')
            <p><strong><a href="{{ 'http://' . request()->getHttpHost() . '/lt' }}"><< Grįžti į pradinį puslapį</a></strong></p>
            @endif
        @endif

        <?php
        switch ($page['category']) {
            case 1:
                $pageCat = 'akadem';
                break;
            case 2:
                $pageCat = 'soc';
                break;
            case 3:
                $pageCat = 'kita';
                break;
            default:
                $pageCat = '';
        }
        ?>

        <div class="row">
        <div class="col-lg-9 infoPage {{$pageCat}}" id="infoPageText">
            {!! $page['text'] !!}
        </div>
        @if($page['mainInfo'] != null)
            <?php
            $items = explode("+", $page['mainInfo']);
            ?>
            <div class="col-md-3" id="infoPageSide">
                @foreach($items as $item)
                    <?php
                    $link = str_replace(array('.', ',', ':', '"', '„', '”', ';', ' '), '', strip_tags($item));
                    $link = str_replace(array('(', ')'), '', $link);
                    $link = str_replace(array('ė', 'Ė', 'ę', 'Ę'), 'e', $link);
                    $link = str_replace(array('ą', 'Ą'), 'a', $link);
                    $link = str_replace(array('ž', 'Ž'), 'z', $link);
                    $link = str_replace(array('į', 'Į'), 'i', $link);
                    $link = str_replace(array('č', 'Č'), 'c', $link);
                    $link = str_replace(array('š', 'Š'), 's', $link);
                    $link = str_replace(array('ų', 'Ų', 'ū', 'Ū'), 'u', $link);
                    ?>

                    <div id="infoPageSideItem" class="infoPageSideItem {{$pageCat}}">
                        <?php
                        $item = str_ireplace('<p>', '', $item);
                        $item = str_ireplace('</p>', '', $item);
                        $item = str_ireplace('<span>', '', $item);
                        $item = str_ireplace('</span>', '', $item);
                        $item = str_replace('../../../', '/lt/', $item);
                        ?>
                        {!! $item !!}
                    </div>
                    <br/>
                @endforeach
            </div>
        @endif
        </div>
    </div>

    <script>
        var scroll = 0;
        var marginTop = 10;
        var pageHeight = $("#infoPageText").height() - 220;
        $(document).ready(function () {
            var bottom = $('.sliderBackground').offset().top + 500;
            $(window).scroll(function () {
                marginTop += ($(document).scrollTop() - scroll);
                if (scroll < bottom) {
                    scroll = $(document).scrollTop();
                }
                if (marginTop < pageHeight) {
                    $("#infoPageSideItem").animate({"marginTop": marginTop + "px"}, {duration: 500, queue: false});
                }
            });
        });

        $(document).ready(function () {
            $(".infoPageSideItem").click(function (event) {
//                event.preventDefault();
                var full_url = $(this).children().attr('href');
                if (full_url.indexOf('#') > -1) {
                    var parts = full_url.split("#");
                    var trgt = parts[1];

                    var target_offset = $("#" + trgt).offset();
                    var target_top = target_offset.top - 75;
                    $('html, body').animate({scrollTop: target_top}, 400, 'easeInSine');
                }
            });
        });

        $("h3").each(function () {
            var me = $(this);
            var value = me.text();
            var InputTitle = value.toLowerCase();
            InputTitle = InputTitle.replace(/[1234567890]/g, '');
            InputTitle = InputTitle.replace(/[ąA]/g, 'a');
            InputTitle = InputTitle.replace(/[čČ]/g, 'c');
            InputTitle = InputTitle.replace(/[ęĘ]/g, 'e');
            InputTitle = InputTitle.replace(/[ėĖ]/g, 'e');
            InputTitle = InputTitle.replace(/[įĮ]/g, 'i');
            InputTitle = InputTitle.replace(/[šŠ]/g, 's');
            InputTitle = InputTitle.replace(/[ųŲūŪ]/g, 'u');
            InputTitle = InputTitle.replace(/[žŽ]/g, 'z');
            InputTitle = InputTitle.replace(/[()]/g, '');
            InputTitle = InputTitle.replace(/[.,:"„”]/g, '');
            InputTitle = InputTitle.replace(/ /g, '');
            me.attr('id', InputTitle);
        });

        if (window.location.href.indexOf('duk') !== -1) {
            $("strong").each(function () {
                var me = $(this);
                me.addClass('dukQuestions');
            });

            /*var ID = window.location.href.split('#')[1];
             var target_offset = $("#" + ID).offset();
             var target_top = target_offset.top - 80;
             $('html, body').animate({scrollTop: target_top}, 750, 'easeInSine');*/
        }
    </script>
    @if (request()->path() == 'lt/sielu-upe')
        <script>
                   
            function restartCandleVideo() {
                document.getElementById("candle").currentTime = 0.1
                document.getElementById("candle").play();
            }   
            
            document.getElementById("candle").addEventListener('ended', restartCandleVideo(), false);

            document.getElementById("lightACandle").addEventListener("click", function () {
                setTimeout(() => {
                document.getElementById("candle").classList.toggle('d-none');
                    }, 100)

            setTimeout(() => {
                // document.getElementById("candle").classList.toggle('d-none');
                document.getElementById("candle").classList.toggle('candle-invisible');
                document.getElementById("candle").classList.toggle('candle-visible');
                document.getElementById("candle").load();
                document.getElementById("candle").play();
            }, 200)
            });
        </script>
    @endif
@endsection
