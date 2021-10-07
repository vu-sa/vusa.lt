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
                <a href="{!! $navbarRoot !!}/lt/apie">Apie VU SA</a><br />
                <a href="{!! $navbarRoot !!}/lt/naujiena/archyvas">Naujienos</a><br />
                <a href="{!! $navbarRoot !!}/lt/lsp">LSP</a><br />
                <a href="{!! $navbarRoot !!}/lt/kontaktai/centrinis-biuras">Kontaktai</a><br />
                <a href="{!! $navbarRoot !!}/lt/duk">D.U.K.</a><br />
                <a href="{!! $navbarRoot !!}/lt/privatumas">Privatumo politika</a><br />          
            </div>
            <div class="col-md-4 col-sm-5 col-xs-12">
                <div class="info">
                    Vilniaus universiteto Studentų atstovybė<br />
                    Įm. k.: 193077294<br />
                    Juridinis asmuo nėra PVM mokėtojas<br />
                    <br />
                    Tel. (8 5) 268 71 44
                    <br /><br />
                    Universiteto g. 3 <br />
                    Observatorijos kiemelis <br />
                    01513, Vilnius, Lietuva<br />
                </div>
            </div>
            <div class="col-md-4 hide_mob col-sm-4">
                <a href="http://www.vu.lt" rel="noopener">Vilniaus universitetas</a><br />
                <a href="{!! $navbarRoot !!}/lt/nuorodos">Naudingos nuorodos</a><br />
                <a href="{!! $navbarRoot !!}/lt/12-procentu-parama">Skirk 1,2 %</a>
            </div>
        </div>
    </nav>
</div>
