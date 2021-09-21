<?php
if (strtotime($news['publish_time']) < strtotime('2016-08-02')) { $imageLocation='images/icons/naujienu_foto.png' ; }
    else { $imageLocation=$news['image']; } ?> <?php if ($news['cat']=='1' ) {
    /* akademo info */ $color='#BD2835' ; $linkColor='redLink' ; $lineColor='newsRedLine' ; } elseif ($news['cat']=='2'
    ) { /* soco info */ $color='#FBB03B' ; $linkColor='yellowLink' ; $lineColor='newsYellowLine' ; } else { /* kita info
    */ $color='#919191' ; $linkColor='greyLink' ; $lineColor='newsGreyLine' ; } ?> 
    
    <article class="news">
        <div style="position:relative">
            <img class="img-fluid news-img" src="{!! asset($imageLocation) !!}" />
            @if ($news['imageAuthor'] != '')
                <div class="img-caption">
                    <small>{{ $news['imageAuthor'] }}</small>
                </div>
            @endif
        </div>
        <h1 class="newsTitle">{{ $news['title'] }}</h1>
        <div class="row">
            <aside class="col-md-3">
                <div class="newsEmptyPlace"></div>

                <?php $publish_date = explode(' ', $news['publish_time']); ?>
                @if ($publish_date[0] != '0000-00-00')
                    <div class="publishDate">{{ $publish_date[0] }}</div>
                @endif
                @if (Lang::locale() == 'lt')
                    <?php $url = $navbarRoot . '/lt/naujiena/' . $news['permalink']; ?>
                @else
                    <?php $url = $navbarRoot . '/en/news/' . $news['permalink']; ?>
                @endif

                <div class="newsSource">
                    <b>Šaltinis</b>: {{ $news['source'] }}
                </div>
                <ul class="socNetworks list-inline {{ $linkColor }}">
                    <li class="facebook list-inline-item">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=http://vusa.lt/naujiena/{{ $news['permalink'] }}"
                            target="_blank">
                            <img src="{!! asset('images/icons/social/news/facebook.png') !!}">
                        </a>
                    </li>

                    <li class="list-inline-item">
                        <a href='http://twitter.com/home/?status="{{ $news['title'] }}" - {{ $url }}'
                            target="_blank" data-target="share_window" data-text="{{ $news['title'] }}">
                            <img src="{!! asset('images/icons/social/news/twitter.png') !!}">
                        </a>
                        <script>
                            ! function(d, s, id) {
                                var js, fjs = d.getElementsByTagName(s)[0],
                                    p = /^http:/.test(d.location) ? 'http' : 'https';
                                if (!d.getElementById(id)) {
                                    js = d.createElement(s);
                                    js.id = id;
                                    js.src = p + '://platform.twitter.com/widgets.js';
                                    fjs.parentNode.insertBefore(js, fjs);
                                }
                            }(document, 'script', 'twitter-wjs');

                        </script>
                    </li>
                    <li class="list-inline-item">
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ $url }}&title={{ $news['title'] }}"
                            target="_blank">
                            <img src="{!! asset('images/icons/social/news/linkedin.png') !!}">
                        </a>
                    </li>
                </ul>
                <div class="{{ $lineColor }}"></div>
                @if ($news['mainPoints'] != '')
                    <div class="newsMainPoints">
                        {!! $news['mainPoints'] !!}
                        <br />
                    </div>
                @endif

                <div class="newsTags">
                    @if ($news['tags'] != null)
                        <?php $tags = explode(';', $news['tags']); ?>
                        @if (Lang::locale() == 'lt')
                            @for ($y = 0; $y < sizeof($tags); $y++)
                                <a href="{!! $navbarRoot !!}/lt/naujiena/zyme/{{ $tags[$y] }}">
                                    <span style="background-color: #919191"
                                        class="newsTag {{ $news['cat'] }}">&nbsp;{{ $tags[$y] }}</span>
                                </a>
                            @endfor
                        @else
                            @for ($y = 0; $y < sizeof($tags); $y++)
                                <a href="{!! $navbarRoot !!}/en/news/tag/{{ $tags[$y] }}">
                                    <span style="background-color: #919191"
                                        class="newsTag {{ $news['cat'] }}">&nbsp;{{ $tags[$y] }}</span>
                                </a>
                            @endfor
                        @endif
                    @endif
                </div>

                <?php
                $link = $news['readMore'];
                if (strpos($link, 'http://www.') !== false) {
                $pos1 = strpos($link, 'http://www.') + 11;
                $link = substr($link, $pos1);
                $pos2 = strpos($link, '/');
                $link = substr($news['readMore'], $pos1, $pos2);
                } elseif (strpos($link, 'https://www.') !== false) {
                $pos1 = strpos($link, 'https://www.') + 12;
                $link = substr($link, $pos1);
                $pos2 = strpos($link, '/');
                $link = substr($news['readMore'], $pos1, $pos2);
                } elseif (strpos($link, 'http://') !== false) {
                $pos1 = strpos($link, 'http://') + 7;
                $link = substr($link, $pos1);
                $pos2 = strpos($link, '/');
                $link = substr($news['readMore'], $pos1, $pos2);
                } elseif (strpos($link, 'https://') !== false) {
                $pos1 = strpos($link, 'https://') + 8;
                $link = substr($link, $pos1);
                $pos2 = strpos($link, '/');
                $link = substr($news['readMore'], $pos1, $pos2);
                } elseif (strpos($link, 'www') !== false) {
                $pos1 = strpos($link, 'www.') + 4;
                $link = substr($link, $pos1);
                if (strpos($link, '/') !== false) {
                $pos2 = strpos($link, '/');
                } else {
                $pos2 = strlen($link);
                }
                $link = substr($news['readMore'], 0, $pos2);
                } else {
                $link = $news['readMore'];
                }
                ?>

                @if ($news['readMore'] != null)
                    @if (Lang::locale() == 'lt')
                        <span style="background-color: {{ $color }}" class="newsReadMore">Daugiau informacijos:
                            <b><a style="color: #fff" target="_blank"
                                    href="{!! $news['readMore'] !!}">{!! $link !!}</a></b>
                        </span>
                    @else
                        <span style="background-color: {{ $color }}" class="newsReadMore">Read more:
                            <b><a style="color: #fff" target="_blank"
                                    href="{!! $news['readMore'] !!}">{!! $link !!}</a></b>
                        </span>
                    @endif
                @endif
            </aside>
            <div class="col-md-8 newsFullText {{ $linkColor }}">
                {!! $news['text'] !!}
            </div>
        </div>
    </article>

    <script>
        $("span.newsTag").hover(function() {
            // akademo info
            if ($(this).hasClass('1')) {
                $(this).css("background-color", "#BD2835");
            }
            // soco info
            if ($(this).hasClass('2')) {
                $(this).css("background-color", "#FBB03B");
            }
        }, function() {
            // kita info
            $(this).css("background-color", "#919191");
        });

    </script>
