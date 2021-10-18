@extends('layouts.user.master')

@section('title'){{$padalinys['fullname']}}@endsection

@section('meta')
    <meta property="og:url" content={{"http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']}}/>
    <meta property="og:type" content="website"/>
    <meta property="og:title" content="VU SA | {{$padalinys['shortname']}}"/>
    <meta property="og:description" content=""/>
    <meta property="og:image" content="/images/icons/logos/vusa.lin.hor.png"/>
@endsection

@section('content')
    <div class="container-padalinys container">
        
        {{--- Padalinio pavadinimas  ---}}
        
        <div>
            <h1 class="pageTitle">{{ __($padalinys['fullname']) }}</h1>
        </div>

        <div class="row">
            <div class="col-lg-10">
                <div class="blackBackground">
                    <div id="newsSlides" class="carousel slide" data-ride="carousel">
                        @if(sizeof($news) > 0)
                            <ol class="carousel-indicators">
                                @for($i = 0; $i < sizeof($news); $i++)
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
                            @foreach($news as $new)
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
                                        <?php $newsLink = '/lt/naujiena/' . $new['permalink'];?>
                                    @else
                                        <?php $newsLink = '/en/news/' . $new['permalink'];?>
                                    @endif

                                    <div class="layer">
                                        <?php
                                        if (strtotime($new['publish_time']) < strtotime('2016-08-02')) {
                                            $imageLocation = 'images/icons/naujienu_foto.png';
                                        } else {
                                            $imageLocation = $new['image'];
                                        }
                                        ?>
                                        <a class="newsSlideLink" href="{!! asset($newsLink) !!}">
                                            <img class="carousel-img" src="{!! asset($imageLocation) !!}"/>
                                        </a>
                                    </div>
                                    <a class="newsSlideLink" href="{!! asset($newsLink) !!}">
                                        <div class="carousel-caption">
                                            <h1>{{ $new['title'] }}</h1>
                                            <p style="line-height: 1.2">{!! $new['short']  !!}</p>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2" id="infoPageSide">
            {{--- By default, pridedami "Kontaktai", "Kuratoriai" kaip puslapio šoniniai mygtukai. ---}}
            @foreach($menuItemsSide as $menuItemSide)
            <div id="infoPageSideItem" class="infoPageSideItem akadem" >
                <a href="{{$menuItemSide['link']}}">{{$menuItemSide['text']}}</a>
            </div>
            <br/>
            @endforeach
            @if ($hasKoordinatoriaiMenuSide == false)
            <div id="infoPageSideItem" class="infoPageSideItem akadem" >
                <a href="{{ Lang::locale() . __('other.coordinator_contacts_url') }}">{{ __('Koordinatoriai') }}</a>
            </div>
            <br/>
            @endif
            @if ($hasKuratoriaiMenuSide == false)
            <div id="infoPageSideItem" class="infoPageSideItem akadem" >
                <a href="{{ Lang::locale() . __('other.mentor_contacts_url') }}">{{ __('Kuratoriai') }}</a>
            </div>
            <br/>
            @endif
            <br/>
            <br/>
            </div>
        </div>

        <div class="align-center col-lg-12">
            <div class="row">
                @foreach($menuItemsBottom as $menuItemBottom)
                    <div class="col-lg-4" style="margin-bottom: 20px">
                        <div id="infoPageSideItem" class="infoPageSideItem akadem">
                            <a href="{{$menuItemBottom['link']}}">{{$menuItemBottom['text']}}</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="align-center col-lg-offset-4 col-lg-3" style="text-align: center">
            @if($additionalInfo != null)
                {!! $additionalInfo->text !!}
            @endif
        </div>
    </div>
    <br/>
@endsection
