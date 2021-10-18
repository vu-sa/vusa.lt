{{-- Navigacinės juostos URL šaknies dinaminis keitimas --}}
<nav class="nav navbar fixed-top navbar-dark blackBackground navbar-expand-lg" style="color:black">
    <div class="container">
        <a class="navbar-brand" href="{!! $navbarRoot !!}/{{ Lang::locale() }}"> <img
                src="{!!  asset('images/icons/logos/vusa_color_logo.png') !!}" alt="VU SA logo" height="50px"> </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarToggle"
            aria-controls="navbarToggle" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarToggle">
            <div class="navbar-nav">

                @if (Lang::locale() == 'en' && count($padaliniaiEn) )
                    <div class="dropdown">
                    <a class="nav-link dropdown-toggle"
                        href="#" data-toggle="dropdown" role="button" aria-haspopup="true"
                        aria-expanded="false">Units <span class="caret"></span>
                    </a>
                    <div class="dropdown-menu">
                        @foreach ($padaliniaiEn as $padalinysEn)
                            <a class="nav-link dropdown-item"
                            href="http://{{ substr($padalinysEn['alias'], 4) }}.vusa.{{ env('APP_ENV') != 'local' ? 'lt' : 'testas:8000' }}/en"
                            >{{ $padalinysEn['shortname'] }}</a>
                        @endforeach
                    </div>
                </div>
                @endif

                @foreach ($navLevel1 as $row1)
                <?php $hasSubItem1 = false; ?>
                @foreach ($navLevel2 as $row2)
                    @if ($row2->pid == $row1->id)
                        <?php $hasSubItem1 = true; ?>
                    @endif
                @endforeach
                @if ($hasSubItem1)
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle"
                            href="{!! $navbarRoot !!}/{!!  $row1->url == '' ? '#' : Lang::locale() . $row1->url !!}"
                            class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                            aria-expanded="false">{{ $row1->text }} <span class="caret"></span>
                        </a>
                        <div class="dropdown-menu">
                            @foreach ($navLevel2 as $row2)
                                @if ($row2->pid == $row1->id)
                                    <?php $hasSubItem2 = false; ?>
                                    @foreach ($navLevel3 as $row3)
                                        @if ($row3->pid == $row2->id)
                                            <?php $hasSubItem2 = true; ?>
                                        @endif
                                    @endforeach
                                    @if ($hasSubItem2)
                                        <div class="dropdown-submenu">
                                            @if (strpos($row2->url, 'http') !== false)
                                                <a class="nav-link dropdown-item"
                                                    href="{!!  $row2->url == '' ? '#' : $row2->url !!}"
                                                    class="dropdown-toggle"
                                                    data-toggle="dropdown">{{ $row2->text }}</a>
                                            @else
                                                <a class="nav-link dropdown-item"
                                                    href="{!! $navbarRoot !!}/{!!  $row2->url == '' ? '#' : Lang::locale() . $row2->url !!}"
                                                    class="dropdown-toggle"
                                                    data-toggle="dropdown">{{ $row2->text }}</a>
                                            @endif

                                            <div class="dropdown-menu">
                                                @foreach ($navLevel3 as $row3)
                                                    @if ($row3->pid == $row2->id)
                                                        <?php $hasSubItem3 = false; ?>
                                                        @foreach ($navLevel4 as $row4)
                                                            @if ($row4->pid == $row3->id)
                                                                <?php $hasSubItem3 = true; ?>
                                                            @endif
                                                        @endforeach
                                                        @if ($hasSubItem3)
                                                            <div class="dropdown-submenu">
                                                                <a class="nav-link dropdown-item"
                                                                    href="{!! $navbarRoot !!}/{!!  $row3->url == '' ? '#' : Lang::locale() . $row3->url !!}"
                                                                    class="dropdown-toggle"
                                                                    data-toggle="dropdown">{{ $row3->text }}</a>
                                                                <div class="dropdown-menu sub-menu">
                                                                    @foreach ($navLevel4 as $row4)
                                                                        @if ($row4->pid == $row3->id)
                                                                            <a class="nav-link dropdown-item"
                                                                                href="{!! $navbarRoot !!}/{!!  $row4->url == '' ? '#' : Lang::locale() . $row4->url !!}">{{ $row4->text }}</a>
                                                                        @endif
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @else
                                                            <a class="nav-link dropdown-item"
                                                                href="{!! $navbarRoot !!}/{!!  $row3->url == '' ? '#' : Lang::locale() . $row3->url !!}">{{ $row3->text }}</a>
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        @if (strpos($row2->url, 'http') !== false)
                                            @if (in_array(request()->path(), ['lt/kontaktai/koordinatoriai', 'lt/studentu-atstovai', 'lt/kontaktai/kuratoriai']))
                                                <a class="nav-link dropdown-item"
                                                    href="{!!  ($row2->url == '' ? '#' : $row2->url) . '/' . request()->path() !!}">{{ $row2->text }}</a>
                                            @else
                                                <a class="nav-link dropdown-item"
                                                    href="{!!  $row2->url == '' ? '#' : $row2->url !!}">{{ $row2->text }}</a>
                                            @endif
                                        @else
                                            <a class="nav-link dropdown-item"
                                                href="{!! $navbarRoot !!}/{!!  $row2->url == '' ? '#' : Lang::locale() . $row2->url !!}">{{ $row2->text }}</a>
                                        @endif

                                    @endif
                                @endif
                            @endforeach
                        </div>
                    </div>
                @else
                    <a class="nav-link"
                        href="{!! $navbarRoot !!}/{{ Lang::locale() . $row1->url }}">{{ $row1->text }}</a>
                @endif
            @endforeach
            <div class="socNet">
                @if (Lang::locale() == 'lt')

                    @if ($en == 1)
                    <a href="/en">
                    @else
                    <a href="{!! $navbarRoot !!}/en">
                    @endif
                    <img src="{!! asset('images/icons/flags/en_veliava.png') !!}"
                        alt="Switch to english language"></a>
                    

                @elseif(Lang::locale() == 'en')
                    
                    <?php $curUrl = Request::url();
                    $pos = strpos($curUrl, '/en');
                    if ($pos !== false) {
                        $curUrl = substr_replace($curUrl, '/lt', $pos, strlen('/en'));
                    } ?>
                    
                    <a href="{{ $curUrl }}"><img src="{!!  asset('images/icons/flags/lt_veliava.png') !!}"
                            alt="Perjungti į lietuvių kalbą"></a>
                @else
                    <a href="/en"><img src="{!!  asset('images/en_veliava.png') !!}"
                            alt="Switch to english language"></a>
                @endif
                <a target="_blank" rel="noopener" href="https://www.facebook.com/VUstudentuatstovybe"><img
                        src="{!!  asset('images/icons/social/menu/facebook.png') !!}"></a>
                <a target="_blank" rel="noopener" href="https://www.instagram.com/vustudentuatstovybe"><img
                        src="{!!  asset('images/icons/social/menu/instagram_black.png') !!}"></a>
                <a target="_blank" rel="noopener" href="https://vu-kd.lt/darbo-skelbimai"><img
                        src="{!!  asset('images/icons/social/menu/kd.png') !!}"></a>
            </div>
            </div>
        </div>
    </div>
</nav>
