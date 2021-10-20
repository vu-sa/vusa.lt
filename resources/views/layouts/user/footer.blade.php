<div id="footer">
    @if (sizeof($banners) > 0)
        <nav class="sliderBackground">
            <div class="container">
                <div class="item">
                    <ul id="content-slider" class="content-slider">
                        @foreach ($banners as $banner)
                            <?php $imageLocation = $banner->value; ?>
                            <li><a target="_blank" rel="noopener" href="{{ $banner->url }}"><img
                                        src="{!! $imageLocation !!}" /></a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </nav>
    @endif

    <nav class="navbar navbar-static-bottom navbar-dark blackBackground">
        <div class="container myfooter">
            <div class="col-md-4 hide_mob col-sm-3">

                @if (Lang::locale() == 'en')
                <a href="{!! $navbarRoot !!}/en/about">About VU SR</a><br />
                {{-- <a href="{!! $navbarRoot !!}/lt/naujiena/archyvas">Naujienos</a><br /> --}}
                <a href="{!! $navbarRoot !!}/en/lsic">LSIC</a><br />
                <a href="{!! $navbarRoot !!}/en/contacts">Contacts</a><br />
                <a href="{!! $navbarRoot !!}/en/operating-documents">Operating documents</a><br />
                <a href="{!! $navbarRoot !!}/en/faq-academic-field">FAQ. Academic field</a><br />
                <a href="{!! $navbarRoot !!}/en/faq-social-field">FAQ. Social field</a><br />
                {{-- <a href="{!! $navbarRoot !!}/lt/privatumas">Privatumo politika</a><br />  --}}

                @else
                <a href="{!! $navbarRoot !!}/lt/apie">Apie VU SA</a><br />
                <a href="{!! $navbarRoot !!}/lt/naujiena/archyvas">Naujienos</a><br />
                <a href="{!! $navbarRoot !!}/lt/lsp">LSP</a><br />
                <a href="{!! $navbarRoot !!}/lt/kontaktai/centrinis-biuras">Kontaktai</a><br />
                <a href="{!! $navbarRoot !!}/lt/duk">D.U.K.</a><br />
                <a href="{!! $navbarRoot !!}/lt/privatumas">Privatumo politika</a><br />    

                @endif
            </div>
            <div class="col-md-4 col-sm-5 col-xs-12">
                <div class="info">
                    {{ __('Vilniaus universiteto Studentų atstovybė') }}<br />
                    {{ __('Įmonės kodas') }}: 193077294<br />
                    {{ __('Juridinis asmuo nėra PVM mokėtojas') }}<br />
                    <br />
                    {{ __('Tel.') }} <a href="tel:+37052687144">(8 5) 268 71 44</a>
                    <br /><br />
                    {{ __('Universiteto g. 3') }} <br />
                    {{ __('Observatorijos kiemelis') }} <br />
                    {{ __('01513, Vilnius, Lietuva') }} <br />
                </div>
            </div>
            <div class="col-md-4 hide_mob col-sm-4">
                @if (Lang::locale() == 'en')
                <a href="http://www.vu.lt/en" rel="noopener">Vilnius University</a><br />
                {{-- <a href="{!! $navbarRoot !!}/lt/12-procentu-parama">Skirk 1,2 %</a> --}}
                @else
                <a href="http://www.vu.lt" rel="noopener">Vilniaus universitetas</a><br />
                <a href="{!! $navbarRoot !!}/lt/nuorodos">Naudingos nuorodos</a><br />
                <a href="{!! $navbarRoot !!}/lt/12-procentu-parama">Skirk 1,2 %</a>
                @endif
            </div>
        </div>
    </nav>
</div>
