@extends('layouts.user.master')

@if(Lang::locale() == 'lt')
    @section('title',"Dažniausiai užduodami klausimai")
@else
    @section('title',"Frequently asked questions")
@endif


@section('content')
    <div class="container">
        @if(Lang::locale() == 'lt')
            <div class="pageTitle">Dažniausiai užduodami klausimai</div>
        @else
            <div class="pageTitle">Frequently asked questions</div>
        @endif

        @if(Lang::locale() == 'lt')
            <div class="row">
                <div class="col-lg-4 col-xs-12">
                    <a href="/lt/duk-socialine-sritis">
                        <div class="dukItem soc">
                            <div class="dukCats">
                                Mokėjimas už mokslą, finansinė parama, stipendijos, paskolos, draudimas.
                            </div>
                            <div class="dukTitle" align="center">
                                <h2>Socialinė sritis</h2>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-4 col-xs-12">
                    <a href="/lt/duk-akademine-sritis">
                        <div class="dukItem akadem">
                            <div class="dukCats">
                                Egzaminai, apeliacijos, perlaikymai, skolos, kurso kartojimas, rotacija, pasirenkamieji dalykai, kursiniai ir bakalauriniai darbai,
                                praktika, akademinės atostogos.
                            </div>
                            <div class="dukTitle" align="center">
                                <h2>Akademinė sritis</h2>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-4 col-xs-12">
                    <a href="/lt/duk-lsp">
                        <div class="dukItem lsp">
                            <div class="dukCats">
                                LSP gamyba, prasitęsimas, praradimas, grąžinimas
                            </div>
                            <div class="dukTitle" align="center">
                                <h2>LSP</h2>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        @else
            <div class="row">
                <div class="col-lg-4 col-xs-12">
                    <a href="/faq-social-affairs">
                        <div class="dukItem soc">
                            <div class="dukCats">
                                Mokėjimas už mokslą, finansinė parama, stipendijos, paskolos, draudimas.
                            </div>
                            <div class="dukTitle" align="center">
                                <h2>Social affairs</h2>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-4 col-xs-12">
                    <a href="/faq-acaedmic-affairs">
                        <div class="dukItem akadem">
                            <div class="dukCats">
                                Egzaminai, apeliacijos, perlaikymai, skolos, kurso kartojimas, rotacija, pasirenkamieji dalykai, kursiniai ir bakalauriniai darbai,
                                praktika, akademinės atostogos.
                            </div>
                            <div class="dukTitle" align="center">
                                <h2>Akademic affairs</h2>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-4 col-xs-12">
                    <a href="/faq-lsid">
                        <div class="dukItem lsp">
                            <div class="dukCats">
                                LSP gamyba, prasitęsimas, praradimas, grąžinimas
                            </div>
                            <div class="dukTitle" align="center">
                                <h2>Lithuanian student identity card</h2>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        @endif
    </div>
    <div style="height: 30px"></div>
@endsection
