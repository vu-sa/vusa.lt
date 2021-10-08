@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Pridėti kontaktą
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/kontaktai/prideti' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li>Kontaktai</li> <li class="active">Pridėti kontaktą</li>': '' !!}
            </ol>
        </section>

        <section class="content">
            @if (count($errors) > 0)
                <div class="alert alert-danger" role="alert">
                    <ul>
                        <?php $errors = array_unique($errors->all());?>
                        @foreach ($errors as $error)
                            <li>{{$error}}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="row">
                <div class="col-md-12">
                    {!! Form::open(['files'=>true ]) !!}

                    <div class="form-group">
                        {{ Form::label('groupname', 'Grupė *') }}

                        @if($sessionInfo->gid !=1 )
                            {{ Form::select('groupname',
                                array('0'=>'--- Parink grupę ---',
                                      'padalinio-biuras' => 'Koordinatoriai',
                                      'padalinio-studentu-atstovai' => 'Studentų atstovai',
                                      'padalinio-taryba' => 'Taryba',
                                      'padalinio-kuratoriai' => 'Padalinio kuratoriai',
                                      'aprasymas-padalinys' => 'Kontaktų aprašymas'),
                                      $groupName, array('class'=>'form-control')
                                      )}}
                        @else
                            {{ Form::select('groupname',
                                array('0'=>'--- Parink grupę ---',
                                      'centrinis-biuras' => 'Centrinis biuras',
                                      'central-office' => 'Centrinis biuras (EN)',
                                      'programos-klubai-projektai' => 'Programos, klubai, projektai',
                                      'studentu-atstovai' => 'Studentų atstovai',
                                      'stiprinimas' => 'VU SA Institucinio stiprinimo fondas',
                                      'padaliniai' => 'VU SA Padaliniai',
                                      'parl-pirm' => 'VU SA Parlamento pirmininkas(-ė)',
                                      'parlamentas' => 'VU SA Parlamentas',
                                      'parlamento-darbo-grupes' => 'VU SA Parlamento darbo grupė',
                                      'revizija' => 'VU SA Revizijos komisija',
                                      'taryba' => 'VU SA Taryba',
                                      'aprasymas' => 'Kontaktų aprašymas'),
                                      $groupName, array('class'=>'form-control')
                                      )}}
                        @endif

                    </div>

                    {{-- Studentų atstovai --}}
                    <div class="form-group" style="display: none" id="grouptitle_input">
                        {{ Form::label('grouptitle', 'Grupės pavadinimas *') }}
                        {{ Form::select('grouptitle',
                            array('0'=>'--- Parink grupę ---',
                                  'VU Akademinio protokolo komisijoje' => 'VU Akademinio protokolo komisijoje',
                                  'VU Bilbliotekos Mokslo taryboje' => 'VU Bilbliotekos Mokslo taryboje',
                                  'VU BUS komisijoje' => 'VU BUS komisijoje',
                                  'VU Centrinėje rinkimų komisijoje' => 'VU Centrinėje rinkimų komisijoje',
                                  'VU Emeritūros, afiliacijos ir garbės vardų komisijoje' => 'VU Emeritūros, afiliacijos ir garbės vardų komisijoje',
                                  'VU Kultūros centro plėtros taryboje' => 'VU Kultūros centro plėtros taryboje',
                                  'VU Sveikatos ir sporto centro taryboje' => 'VU Sveikatos ir sporto centro taryboje',
                                  'VU Vienkartinių tikslinių stipendijų skirstymo komisijoje' => 'VU Vienkartinių tikslinių stipendijų skirstymo komisijoje',
                                  'VU Vienkartinių socialinių stipendijų skirstymo komisijoje' => 'VU Vienkartinių socialinių stipendijų skirstymo komisijoje',
                                  'VU Centrinėje akademinės etikos komisijoje' => 'VU Centrinėje akademinės etikos komisijoje',
                                  'VU Centrinėje ginčų nagrinėjimo komisijoje' => 'VU Centrinėje ginčų nagrinėjimo komisijoje',
                                  'VU Priėmimo komisijoje' => 'VU Priėmimo komisijoje',
                                  'VU Senato Kokybės ir plėtros komitete' => 'VU Senato Kokybės ir plėtros komitete',
                                  'VU Senato Mokslo komitete' => 'VU Senato Mokslo komitete',
                                  'VU Senato Studijų komitete' => 'VU Senato Studijų komitete',
                                  'VU Taryboje' => 'VU Taryboje'),
                                  '0', array('class'=>'form-control')
                                  )}}
                    </div>

                    {{-- Visi kontaktai --}}
                    @if($sessionInfo->gid != 1)
                        <div class="form-group" style="display: none" id="grouptitleDescription_input">
                            {{ Form::label('grouptitleDescription', 'Kontakto grupės pavadinimas *') }}
                            {{ Form::select('grouptitleDescription',
                            array('0'=>'--- Parink grupę ---',
                                      'padalinio-biuras' => 'Koordinatoriai',
                                      'padalinio-taryba' => 'Taryba',
                                      'padalinio-kuratoriai' => 'Kuratoriai',
                                      'padalinio-studentu-atstovai' => 'Studentų atstovai'),
                                      '0', array('class'=>'form-control'))}}
                        </div>
                    @else
                        <div class="form-group" style="display: none" id="grouptitleDescription_input">
                            {{ Form::label('grouptitleDescription', 'Kontakto grupės pavadinimas *') }}
                            {{ Form::select('grouptitleDescription',
                            array('0'=>'--- Parink grupę ---',
                                      'centrinis-biuras' => 'Centrinis biuras',
                                      'central-office' => 'Centrinis biuras EN',
                                      'studentu-atstovai' => 'Studentų atstovai',
                                      'programos-klubai-projektai' => 'Programos, klubai, projektai',
                                      'revizija' => 'Revizijos komisija',
                                      'padaliniai' => 'Padaliniai',
                                      'institucinio-stiprinimo-fondas' => 'VU SA Institucinio stiprinimo fondas'
                                      ),
                                      '0', array('class'=>'form-control'))}}
                        </div>
                    @endif

                    {{-- BIURAS --}}
                    <div class="form-group" style="display: none" id="name_input">
                        {{ Form::label('name', 'Vardas ir pavardė *') }}
                        {{ Form::text('name', '', array('class'=>'form-control')) }}
                    </div>

                    {{-- PARLAMENTO DARBO GRUPES --}}
                    <div class="form-group" style="display: none" id="DBpirmName_input">
                        {{ Form::label('nameP', 'Pirmininko vardas ir pavardė *') }}
                        {{ Form::text('nameP', '', array('class'=>'form-control')) }}
                    </div>

                    {{-- revizija --}}
                    <div class="form-group" style="display: none" id="name_revizija_input">
                        {{ Form::label('nameRevizija', 'Vardas ir pavardė (pirmininko/ės) *') }}
                        {{ Form::text('nameRevizija', '', array('class'=>'form-control')) }}
                    </div>

                    {{-- BIURAS --}}
                    <div class="form-group" style="display: none" id="duties_input">
                        {{ Form::label('duties', 'Pareigos *') }}
                        {{ Form::text('duties', '', array('class'=>'form-control')) }}
                    </div>

                    {{-- PADALINYS--}}
                    <div class="form-group" style="display: none" id="name_short_input">
                        {{ Form::label('name_short', 'Padalinio pavadinimas (trumpas) *') }}
                        {{ Form::text('name_short', '', array('class'=>'form-control')) }}
                    </div>

                    {{-- PADALINYS--}}
                    <div class="form-group" style="display: none" id="name_full_input">
                        {{ Form::label('name_full', 'Padalinio pavadinimas (pilnas) *') }}
                        {{ Form::text('name_full', '', array('class'=>'form-control')) }}
                    </div>

                    {{-- PARLAMENTO DARBO GRUPES --}}
                    <div class="form-group" style="display: none" id="DBname_input">
                        {{ Form::label('name_full', 'Darbo grupės pavadinimas *') }}
                        {{ Form::text('name_full', '', array('class'=>'form-control')) }}
                    </div>

                    {{--Programos, klubai, projektai--}}
                    <div class="form-group" style="display: none" id="name_full_programos_input">
                        {{ Form::label('name_full_programos', 'Programos pavadinimas *') }}
                        {{ Form::text('name_full_programos', '', array('class'=>'form-control')) }}
                    </div>

                    {{-- PADALINYS--}}
                    <div class="form-group" style="display: none" id="address_input">
                        {{ Form::label('address', 'Adresas *') }}
                        {{ Form::text('address', '', array('class'=>'form-control')) }}
                    </div>

                    {{-- BIURAS, PADALINYS --}}
                    <div class="form-group" style="display: none" id="phone_input">
                        {{ Form::label('phone', 'Telefono numeris *') }}
                        {{ Form::text('phone', '', array('class'=>'form-control')) }}
                    </div>

                    {{-- BIURAS, PADALINYS --}}
                    <div class="form-group" style="display: none" id="email_input">
                        {{ Form::label('email', 'El. paštas *') }}
                        {{ Form::text('email', '', array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group" style="display: none" id="lang_input">
                        {{ Form::label('lang', 'Kalba *') }}
                        {{ Form::select('lang', array('lt' => 'LT', 'en' => 'EN'), '', array('class'=>'form-control') )}}
                    </div>

                    {{-- BIURAS --}}
                    <div class="form-group" style="display: none" id="infoText_input">
                        {{ Form::label('infoText', 'Informacinis tekstas *') }}
                        {{ Form::textarea('infoText', '', array('class'=>'form-control edit', 'rows'=>5)) }}
                    </div>

                    {{-- BIURAS --}}
                    <div class="form-group photo">
                        {{ Form::label('image', 'Kontakto nuotrauka *') }}
                        <span class="input-group-btn">
                            <a id="lfm" data-input="image" class="btn btn-primary">
                            <i class="far fa-image"></i> Pasirinkti nuotrauką
                            </a>
                          </span>
                        {{ Form::text('image', '', array('class'=>'form-control', 'readonly'=>'readonly')) }}
                    </div>

                    {{ Form::hidden('frame_width', '', array('class'=>'form-control')) }}
                    {{ Form::hidden('frame_height', '', array('class'=>'form-control')) }}

                    {{ Form::hidden('width', '', array('class'=>'form-control')) }}
                    {{ Form::hidden('height', '', array('class'=>'form-control')) }}

                    {{ Form::hidden('x', '', array('class'=>'form-control')) }}
                    {{ Form::hidden('y', '', array('class'=>'form-control')) }}

                    {{-- PADALINYS--}}
                    <div class="form-group" style="display: none" id="webpage_input">
                        {{ Form::label('webpage', 'Tinklalapio adreas') }}
                        {{ Form::text('webpage', '', array('class'=>'form-control')) }}
                    </div>

                    {{--PARLAMENTAS--}}
                    <div class="form-group" style="display: none" id="members_input">
                        {{ Form::label('members', 'Padaliniai/nariai (atskirti kableliu) *') }}
                        {{ Form::text('members', '', array('class'=>'form-control')) }}
                    </div>

                    {{Form::submit('Sukurti',['class'=>'btn btn-primary', 'style'=>'display: none', 'id'=>'submit'])}}

                    {{ Form::close() }}
                </div>
            </div>
        </section>
    </div>

    <script>
        
        $("#cropBtn").on('click', function () {
            $("#imageSRC").attr("src", document.getElementById("image").value);
        });
        $(document).ready(function () {
            $('select[name=groupname]').change(function (e) {
                if ($('select[name=groupname]').val() == '0') {
                    $('#name_short_input').hide();
                    $('#name_full_input').hide();
                    $('#address_input').hide();
                    $('#webpage_input').hide();
                    $('#members_input').hide();
                    $('#name_input').hide();
                    $('#duties_input').hide();
                    $('#phone_input').hide();
                    $('#email_input').hide();
                    $('#infoText_input').hide();
                    $('#photo_input').hide();
                    $('#name_revizija_input').hide();
                    $('#grouptitle_input').hide();
                    $('#DBpirmName_input').hide();
                    $('#DBname_input').hide();
                    $('#submit').hide();
                    $('#lang_input').hide();
                }
                if ($('select[name=groupname]').val() == 'centrinis-biuras' || $('select[name=groupname]').val() == 'central-office' || $('select[name=groupname]').val() == 'biuras') {
                    $('#name_input').show();
                    $('#duties_input').show();
                    $('#phone_input').show();
                    $('#email_input').show();
                    $('#infoText_input').show();
                    $('#photo_input').show();
                    $('#lang_input').show();
                    $('#submit').show();

                    $('#name_short_input').hide();
                    $('#name_full_input').hide();
                    $('#address_input').hide();
                    $('#webpage_input').hide();
                    $('#members_input').hide();
                    $('#name_revizija_input').hide();
                    $('#grouptitle_input').hide();
                    $('#DBpirmName_input').hide();
                    $('#DBname_input').hide();
                }
                if ($('select[name=groupname]').val() == 'padaliniai') {
                    $('#name_short_input').show();
                    $('#name_full_input').show();
                    $('#address_input').show();
                    $('#phone_input').show();
                    $('#email_input').show();
                    $('#webpage_input').show();
                    $('#lang_input').show();
                    $('#submit').show();

                    $('#members_input').hide();
                    $('#name_input').hide();
                    $('#duties_input').hide();
                    $('#infoText_input').hide();
                    $('#photo_input').hide();
                    $('#name_revizija_input').hide();
                    $('#grouptitle_input').hide();
                    $('#DBpirmName_input').hide();
                    $('#DBname_input').hide();
                }
                if ($('select[name=groupname]').val() == 'parl-pirm') {
                    $('#name_input').show();
                    $('#phone_input').show();
                    $('#email_input').show();
                    $('#lang_input').show();
                    $('#submit').show();

                    $('#name_short_input').hide();
                    $('#name_full_input').hide();
                    $('#address_input').hide();
                    $('#webpage_input').hide();
                    $('#members_input').hide();
                    $('#duties_input').hide();
                    $('#infoText_input').hide();
                    $('#photo_input').hide();
                    $('#name_revizija_input').hide();
                    $('#grouptitle_input').hide();
                    $('#DBpirmName_input').hide();
                    $('#DBname_input').hide();
                }
                if ($('select[name=groupname]').val() == 'parlamentas') {
                    $('#name_short_input').show();
                    $('#members_input').show();
                    $('#lang_input').show();
                    $('#submit').show();

                    $('#name_input').hide();
                    $('#name_full_input').hide();
                    $('#address_input').hide();
                    $('#webpage_input').hide();
                    $('#duties_input').hide();
                    $('#phone_input').hide();
                    $('#email_input').hide();
                    $('#infoText_input').hide();
                    $('#photo_input').hide();
                    $('#name_revizija_input').hide();
                    $('#grouptitle_input').hide();
                    $('#DBpirmName_input').hide();
                    $('#DBname_input').hide();
                }

                if ($('select[name=groupname]').val() == 'parlamento-darbo-grupes') {
//                    $('#DBpirmName_input').show();
//                    $('#DBname_input').show();
                    $('#phone_input').show();
                    $('#email_input').show();
                    $('#members_input').show();
                    $('#lang_input').show();
                    $('#submit').show();

                    $('#name_short_input').hide();
                    $('#address_input').hide();
                    $('#webpage_input').hide();
                    $('#duties_input').hide();
                    $('#infoText_input').hide();
                    $('#photo_input').hide();
                    $('#name_revizija_input').hide();
                    $('#grouptitle_input').hide();
                    $('#DBpirmName_input').hide();
                    $('#DBname_input').hide();
                }

                if ($('select[name=groupname]').val() == 'taryba') {
                    $('#name_short_input').show();
                    $('#name_input').show();
                    $('#email_input').show();
                    $('#phone_input').show();
                    $('#lang_input').show();
                    $('#submit').show();

                    $('#name_full_input').hide();
                    $('#webpage_input').hide();
                    $('#duties_input').hide();
                    $('#address_input').hide();
                    $('#infoText_input').hide();
                    $('#members_input').hide();
                    $('#photo_input').hide();
                    $('#name_revizija_input').hide();
                    $('#grouptitle_input').hide();
                    $('#DBpirmName_input').hide();
                    $('#DBname_input').hide();
                }

                if ($('select[name=groupname]').val() == 'padalinio-taryba' ||
                        $('select[name=groupname]').val() == 'padalinio-studentu-atstovai') {
                    $('#name_input').show();
                    $('#email_input').show();
                    $('#phone_input').show();
                    $('#lang_input').show();
                    $('#photo_input').show();
                    $('#submit').show();
                    $('#duties_input').show();

                    $('#name_short_input').hide();
                    $('#name_full_input').hide();
                    $('#webpage_input').hide();
                    $('#address_input').hide();
                    $('#infoText_input').hide();
                    $('#members_input').hide();
                    $('#name_revizija_input').hide();
                    $('#grouptitle_input').hide();
                    $('#DBpirmName_input').hide();
                    $('#DBname_input').hide();
                }

                if ($('select[name=groupname]').val() == 'padalinio-biuras' || $('select[name=groupname]').val() == 'padalinio-kuratoriai') {
                    $('#name_input').show();
                    $('#email_input').show();
                    $('#phone_input').show();
                    $('#lang_input').show();
                    $('#photo_input').show();
                    $('#infoText_input').show();
                    $('#duties_input').show();
                    $('#submit').show();

                    $('#name_short_input').hide();
                    $('#name_full_input').hide();
                    $('#webpage_input').hide();
                    $('#address_input').hide();
                    $('#members_input').hide();
                    $('#name_revizija_input').hide();
                    $('#grouptitle_input').hide();
                    $('#DBpirmName_input').hide();
                    $('#DBname_input').hide();
                }

                if ($('select[name=groupname]').val() == 'programos-klubai-projektai') {
                    $('#name_full_programos_input').show();
                    $('#name_input').show();
                    $('#phone_input').show();
                    $('#email_input').show();
                    $('#lang_input').show();
                    $('#submit').show();

                    $('#name_short_input').hide();
                    $('#address_input').hide();
                    $('#webpage_input').hide();
                    $('#members_input').hide();
                    $('#duties_input').hide();
                    $('#infoText_input').hide();
                    $('#photo_input').hide();
                    $('#name_revizija_input').hide();
                    $('#grouptitle_input').hide();
                    $('#DBpirmName_input').hide();
                    $('#DBname_input').hide();
                }
                if ($('select[name=groupname]').val() == 'revizija') {
                    $('#name_revizija_input').show();
                    $('#phone_input').show();
                    $('#email_input').show();
                    $('#members_input').show();
                    $('#lang_input').show();
                    $('#submit').show();

                    $('#duties_input').hide();
                    $('#name_input').hide();
                    $('#name_short_input').hide();
                    $('#name_full_input').hide();
                    $('#address_input').hide();
                    $('#webpage_input').hide();
                    $('#infoText_input').hide();
                    $('#photo_input').hide();
                    $('#grouptitle_input').hide();
                    $('#DBpirmName_input').hide();
                    $('#DBname_input').hide();
                }

                if ($('select[name=groupname]').val() == 'stiprinimas') {
                    $('#name_input').show();
                    $('#phone_input').show();
                    $('#email_input').show();
                    $('#members_input').show();
                    $('#lang_input').show();
                    $('#submit').show();

                    $('#duties_input').hide();
                    $('#name_short_input').hide();
                    $('#name_full_input').hide();
                    $('#address_input').hide();
                    $('#webpage_input').hide();
                    $('#infoText_input').hide();
                    $('#photo_input').hide();
                    $('#grouptitle_input').hide();
                    $('#DBpirmName_input').hide();
                    $('#DBname_input').hide();
                }

                if ($('select[name=groupname]').val() == 'studentu-atstovai') {
                    $('#grouptitle_input').show();
                    $('#name_input').show();
                    $('#phone_input').show();
                    $('#email_input').show();
                    $('#submit').show();

                    $('#members_input').hide();
                    $('#duties_input').hide();
                    $('#name_short_input').hide();
                    $('#name_full_input').hide();
                    $('#address_input').hide();
                    $('#webpage_input').hide();
                    $('#infoText_input').hide();
                    $('#photo_input').hide();
                    $('#DBpirmName_input').hide();
                    $('#DBname_input').hide();
                }

                if ($('select[name=groupname]').val() == 'aprasymas') {
                    $('#infoText_input').show();
                    $('#grouptitleDescription_input').show();
                    $('#submit').show();

                    $('#name_input').hide();
                    $('#phone_input').hide();
                    $('#email_input').hide();
                    $('#members_input').hide();
                    $('#duties_input').hide();
                    $('#name_short_input').hide();
                    $('#name_full_input').hide();
                    $('#address_input').hide();
                    $('#webpage_input').hide();
                    $('#photo_input').hide();
                    $('#DBpirmName_input').hide();
                    $('#DBname_input').hide();
                }

                if ($('select[name=groupname]').val() == 'aprasymas-padalinys') {
                    $('#infoText_input').show();
                    $('#grouptitleDescription_input').show();
                    $('#photo_input').show();
                    $('#submit').show();

                    $('#name_input').hide();
                    $('#phone_input').hide();
                    $('#email_input').hide();
                    $('#members_input').hide();
                    $('#duties_input').hide();
                    $('#name_short_input').hide();
                    $('#name_full_input').hide();
                    $('#address_input').hide();
                    $('#webpage_input').hide();
                    $('#DBpirmName_input').hide();
                    $('#DBname_input').hide();
                }
            });
            if ($('select[name=groupname]').val() == '0') {
                $('#name_short_input').hide();
                $('#name_full_input').hide();
                $('#address_input').hide();
                $('#webpage_input').hide();
                $('#members_input').hide();
                $('#name_input').hide();
                $('#duties_input').hide();
                $('#phone_input').hide();
                $('#email_input').hide();
                $('#infoText_input').hide();
                $('#photo_input').hide();
                $('#name_revizija_input').hide();
                $('#grouptitle_input').hide();
                $('#submit').hide();
                $('#DBpirmName_input').hide();
                $('#DBname_input').hide();
            }
            if ($('select[name=groupname]').val() == 'centrinis-biuras' || $('select[name=groupname]').val() == 'central-office') {
                $('#name_input').show();
                $('#duties_input').show();
                $('#phone_input').show();
                $('#email_input').show();
                $('#infoText_input').show();
                $('#photo_input').show();
                $('#submit').show();

                $('#name_short_input').hide();
                $('#name_full_input').hide();
                $('#address_input').hide();
                $('#webpage_input').hide();
                $('#members_input').hide();
                $('#name_revizija_input').hide();
                $('#grouptitle_input').hide();
                $('#DBpirmName_input').hide();
                $('#DBname_input').hide();
            }
            if ($('select[name=groupname]').val() == 'padaliniai') {
                $('#name_short_input').show();
                $('#name_full_input').show();
                $('#address_input').show();
                $('#phone_input').show();
                $('#email_input').show();
                $('#webpage_input').show();
                $('#submit').show();

                $('#members_input').hide();
                $('#name_input').hide();
                $('#duties_input').hide();
                $('#infoText_input').hide();
                $('#photo_input').hide();
                $('#name_revizija_input').hide();
                $('#grouptitle_input').hide();
                $('#DBpirmName_input').hide();
                $('#DBname_input').hide();
            }
            if ($('select[name=groupname]').val() == 'parl-pirm') {
                $('#name_input').show();
                $('#phone_input').show();
                $('#email_input').show();
                $('#submit').show();

                $('#name_short_input').hide();
                $('#name_full_input').hide();
                $('#address_input').hide();
                $('#webpage_input').hide();
                $('#members_input').hide();
                $('#duties_input').hide();
                $('#infoText_input').hide();
                $('#photo_input').hide();
                $('#name_revizija_input').hide();
                $('#grouptitle_input').hide();
                $('#DBpirmName_input').hide();
                $('#DBname_input').hide();
            }

            if ($('select[name=groupname]').val() == 'parlamentas') {
                $('#name_short_input').show();
                $('#members_input').show();
                $('#submit').show();

                $('#name_input').hide();
                $('#name_full_input').hide();
                $('#address_input').hide();
                $('#webpage_input').hide();
                $('#duties_input').hide();
                $('#phone_input').hide();
                $('#email_input').hide();
                $('#infoText_input').hide();
                $('#photo_input').hide();
                $('#name_revizija_input').hide();
                $('#grouptitle_input').hide();
                $('#DBpirmName_input').hide();
                $('#DBname_input').hide();
            }

            if ($('select[name=groupname]').val() == 'parlamento-darbo-grupes') {
                $('#DBpirmName_input').show();
                $('#DBname_input').show();
                $('#phone_input').show();
                $('#email_input').show();
                $('#members_input').show();
                $('#submit').show();

                $('#name_short_input').hide();
                $('#address_input').hide();
                $('#webpage_input').hide();
                $('#duties_input').hide();
                $('#infoText_input').hide();
                $('#photo_input').hide();
                $('#name_revizija_input').hide();
                $('#grouptitle_input').hide();
            }

            if ($('select[name=groupname]').val() == 'taryba') {
                $('#name_short_input').show();
                $('#name_input').show();
                $('#email_input').show();
                $('#phone_input').show();
                $('#submit').show();

                $('#name_full_input').hide();
                $('#webpage_input').hide();
                $('#duties_input').hide();
                $('#address_input').hide();
                $('#infoText_input').hide();
                $('#members_input').hide();
                $('#photo_input').hide();
                $('#name_revizija_input').hide();
                $('#grouptitle_input').hide();
                $('#DBpirmName_input').hide();
                $('#DBname_input').hide();
            }

            if ($('select[name=groupname]').val() == 'padalinio-taryba' ||
                    $('select[name=groupname]').val() == 'padalinio-studentu-atstovai') {
                $('#name_input').show();
                $('#email_input').show();
                $('#phone_input').show();
                $('#photo_input').show();
                $('#duties_input').show();
                $('#submit').show();

                $('#name_short_input').hide();
                $('#name_full_input').hide();
                $('#webpage_input').hide();
                $('#address_input').hide();
                $('#members_input').hide();
                $('#name_revizija_input').hide();
                $('#grouptitle_input').hide();
                $('#DBpirmName_input').hide();
                $('#infoText_input').hide();
                $('#DBname_input').hide();
            }

            if ($('select[name=groupname]').val() == 'padalinio-biuras' || $('select[name=groupname]').val() == 'padalinio-kuratoriai') {
                $('#name_input').show();
                $('#email_input').show();
                $('#phone_input').show();
                $('#photo_input').show();
                $('#duties_input').show();
                $('#infoText_input').show();
                $('#submit').show();

                $('#name_short_input').hide();
                $('#name_full_input').hide();
                $('#webpage_input').hide();
                $('#address_input').hide();
                $('#members_input').hide();
                $('#name_revizija_input').hide();
                $('#grouptitle_input').hide();
                $('#DBpirmName_input').hide();
                $('#DBname_input').hide();
            }

            if ($('select[name=groupname]').val() == 'programos-klubai-projektai') {
                $('#name_full_programos_input').show();
                $('#name_input').show();
                $('#phone_input').show();
                $('#email_input').show();
                $('#submit').show();

                $('#name_short_input').hide();
                $('#address_input').hide();
                $('#webpage_input').hide();
                $('#members_input').hide();
                $('#duties_input').hide();
                $('#infoText_input').hide();
                $('#photo_input').hide();
                $('#name_revizija_input').hide();
                $('#grouptitle_input').hide();
                $('#DBpirmName_input').hide();
                $('#DBname_input').hide();
            }
            if ($('select[name=groupname]').val() == 'revizija') {
                $('#name_revizija_input').show();
                $('#phone_input').show();
                $('#email_input').show();
                $('#members_input').show();
                $('#submit').show();

                $('#duties_input').hide();
                $('#name_input').hide();
                $('#name_short_input').hide();
                $('#name_full_input').hide();
                $('#address_input').hide();
                $('#webpage_input').hide();
                $('#infoText_input').hide();
                $('#photo_input').hide();
                $('#grouptitle_input').hide();
                $('#DBpirmName_input').hide();
                $('#DBname_input').hide();
            }

            if ($('select[name=groupname]').val() == 'stiprinimas') {
                $('#name_input').show();
                $('#phone_input').show();
                $('#email_input').show();
                $('#members_input').show();
                $('#submit').show();

                $('#duties_input').hide();
                $('#name_short_input').hide();
                $('#name_full_input').hide();
                $('#address_input').hide();
                $('#webpage_input').hide();
                $('#infoText_input').hide();
                $('#photo_input').hide();
                $('#grouptitle_input').hide();
                $('#DBpirmName_input').hide();
                $('#DBname_input').hide();
            }

            if ($('select[name=groupname]').val() == 'studentu-atstovai') {
                $('#grouptitle_input').show();
                $('#name_input').show();
                $('#phone_input').show();
                $('#email_input').show();
                $('#submit').show();

                $('#members_input').hide();
                $('#duties_input').hide();
                $('#name_short_input').hide();
                $('#name_full_input').hide();
                $('#address_input').hide();
                $('#webpage_input').hide();
                $('#infoText_input').hide();
                $('#photo_input').hide();
                $('#DBpirmName_input').hide();
                $('#DBname_input').hide();
            }

            if ($('select[name=groupname]').val() == 'aprasymas') {
                $('#infoText_input').show();
                $('#grouptitleDescription_input').show();
                $('#submit').show();

                $('#name_input').hide();
                $('#phone_input').hide();
                $('#email_input').hide();
                $('#members_input').hide();
                $('#duties_input').hide();
                $('#name_short_input').hide();
                $('#name_full_input').hide();
                $('#address_input').hide();
                $('#webpage_input').hide();
                $('#photo_input').hide();
                $('#DBpirmName_input').hide();
                $('#DBname_input').hide();
            }

            if ($('select[name=groupname]').val() == 'aprasymas-padalinys') {
                $('#infoText_input').show();
                $('#grouptitleDescription_input').show();
                $('#photo_input').show();
                $('#submit').show();

                $('#name_input').hide();
                $('#phone_input').hide();
                $('#email_input').hide();
                $('#members_input').hide();
                $('#duties_input').hide();
                $('#name_short_input').hide();
                $('#name_full_input').hide();
                $('#address_input').hide();
                $('#webpage_input').hide();
                $('#DBpirmName_input').hide();
                $('#DBname_input').hide();
            }
        });
    </script>

<script>
        
    (function( $ ){

$.fn.filemanager = function(type, options) {
  type = type || 'file';

  this.on('click', function(e) {
    var route_prefix = (options && options.prefix) ? options.prefix : '/filemanager';
    var target_input = $('#' + $(this).data('input'));
    var target_preview = $('#' + $(this).data('preview'));
    window.open(route_prefix + '?type=' + type, 'FileManager', 'width=900,height=600');
    window.SetUrl = function (items) {
      var file_path = items.map(function (item) {
        return item.url;
      }).join(',');

      // set the value of the desired input to image url
      target_input.val('').val(file_path).trigger('change');

      // clear previous preview
      target_preview.html('');

      // set or change the preview image src
      items.forEach(function (item) {
        target_preview.append(
          $('<img>').css('height', '5rem').attr('src', item.thumb_url)
        );
      });

      // trigger change event
      target_preview.trigger('change');
    };
    return false;
  });
}

})(jQuery);


$('#lfm').filemanager('image');

    </script>
@endsection