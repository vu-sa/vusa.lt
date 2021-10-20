@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Redaguoti kontaktą
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/kontaktai/{id}/redaguoti' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li>Kontaktai</li> <li class="active">Redaguoti kontaktą</li>': '' !!}
            </ol>
        </section>

        <section class="content">
            @if (count($errors) > 0)
                <div class="alert alert-danger" role="alert">
                    <ul>
                        <?php $errors = array_unique($errors->all());?>
                        @foreach ($errors as $error)
                            @if($error == 'validation.required')
                                <li>Naudotojo vardo, vardo ir pavedės bei slaptažodžio laukai turi būti užpildyti</li>
                            @elseif($error == 'validation.same')
                                <li>Slaptažodis abiejuose laukuose turi būti vienodas</li>
                            @elseif($error == 'validation.min.string')
                                <li>Slaptažodžio ilgis turi būti 7 ar daugiau simbolių ilgio</li>
                            @elseif($error == 'validation.unique')
                                <li>Vartotojas su tokiu vardu jau egzistuoja</li>
                            @else
                                <li>{{$error}}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="row ">
                <div class="col-md-12">
                    {!! Form::model($contats, ['files'=>true, 'method' => 'PATCH'  ]) !!}
                    <div class="form-group">
                        {{ Form::label('groupname', 'Grupė *') }}

                        @if($sessionInfo->gid != 1)
                            {{ Form::select('groupname',
                                array('0'=>'--- Parink grupę ---',
                                      'padalinio-biuras' => 'Koordinatoriai',
                                      'padalinio-biuras-en' => 'Koordinatoriai EN',
                                    //   'padalinio-studentu-atstovai' => 'Studentų atstovai',
                                    //   'padalinio-taryba' => 'Padalinio taryba',
                                      'padalinio-kuratoriai' => 'Padalinio kuratoriai',
                                      'padalinio-kuratoriai-en' => 'Padalinio kuratoriai EN',
                                      'aprasymas-padalinys' => 'Kontaktų aprašymas'),
                                      $contats->groupname, array('class'=>'form-control')
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
                                      $contats->groupname, array('class'=>'form-control')
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
                                  null, array('class'=>'form-control')
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
                                      $contats->grouptitle, array('class'=>'form-control'))}}
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
                                      $contats->grouptitle, array('class'=>'form-control'))}}
                        </div>
                    @endif

                    {{-- BIURAS --}}
                    <div class="form-group" style="display: none" id="name_input">
                        {{ Form::label('name', 'Vardas ir pavardė *') }}
                        {{ Form::text('name', null, array('class'=>'form-control')) }}
                    </div>

                    {{-- PARLAMENTO DARBO GRUPES --}}
                    <div class="form-group" style="display: none" id="DBpirmName_input">
                        {{ Form::label('nameP', 'Pirmininko vardas ir pavardė *') }}
                        {{ Form::text('nameP', null, array('class'=>'form-control')) }}
                    </div>

                    {{-- revizija --}}
                    <div class="form-group" style="display: none" id="name_revizija_input">
                        {{ Form::label('nameRevizija', 'Vardas ir pavardė *') }}
                        {{ Form::text('nameRevizija', $contats['name'], array('class'=>'form-control')) }}
                    </div>

                    {{-- BIURAS --}}
                    <div class="form-group" style="display: none" id="duties_input">
                        {{ Form::label('duties', 'Pareigos *') }}
                        {{ Form::text('duties', null, array('class'=>'form-control')) }}
                    </div>

                    {{-- PADALINYS--}}
                    <div class="form-group" style="display: none" id="name_short_input">
                        {{ Form::label('name_short', 'Padalinio pavadinimas (trumpas) *') }}
                        {{ Form::text('name_short', null, array('class'=>'form-control')) }}
                    </div>

                    {{-- PADALINYS--}}
                    <div class="form-group" style="display: none" id="name_full_input">
                        <?php $label = '';?>
                        @if($contats->groupname == 'padaliniai')
                            <?php $label = 'Padalinio pavadinimas (pilnas) *';?>
                        @endif
                        @if($contats->groupname == 'parlamento-darbo-grupes')
                            <?php $label = 'Darbo grupės pavadinimas *';?>
                        @endif

                        {{ Form::label('name_full', $label) }}
                        {{ Form::text('name_full', null, array('class'=>'form-control')) }}
                    </div>

                    {{--Programos, klubai, projektai--}}
                    <div class="form-group" style="display: none" id="name_full_programos_input">
                        {{ Form::label('name_full_programos', 'Programos pavadinimas *') }}
                        {{ Form::text('name_full_programos', null,array('class'=>'form-control')) }}
                    </div>

                    {{-- PADALINYS--}}
                    <div class="form-group" style="display: none" id="address_input">
                        {{ Form::label('address', 'Adresas *') }}
                        {{ Form::text('address', null, array('class'=>'form-control')) }}
                    </div>

                    {{-- BIURAS, PADALINYS --}}
                    <div class="form-group" style="display: none" id="phone_input">
                        {{ Form::label('phone', 'Telefono numeris *') }}
                        {{ Form::text('phone', null, array('class'=>'form-control')) }}
                    </div>

                    {{-- BIURAS, PADALINYS --}}
                    <div class="form-group" style="display: none" id="email_input">
                        {{ Form::label('email', 'El. paštas *') }}
                        {{ Form::text('email', null, array('class'=>'form-control')) }}
                    </div>

                    {{-- <div class="form-group">
                        {{ Form::label('lang', 'Kalba *') }}
                        {{ Form::select('lang', array('lt' => 'LT', 'en' => 'EN'), null, array('class'=>'form-control') )}}
                    </div> --}}

                    {{-- BIURAS --}}
                    <div class="form-group" style="display: none" id="infoText_input">
                        {{ Form::label('infoText', 'Informacinis tekstas *') }}
                        {{ Form::textarea('infoText', null, array('class'=>'form-control edit', 'rows'=>5)) }}
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
                        {{ Form::text('webpage', null, array('class'=>'form-control')) }}
                    </div>

                    {{--PARLAMENTAS, revizija, ISFas--}}
                    <div class="form-group" style="display: none" id="members_input">
                        {{ Form::label('members', 'Padaliniai/nariai (atskirti kableliu) *') }}
                        {{ Form::text('members', null, array('class'=>'form-control')) }}
                    </div>

                    {{Form::submit('Atnaujinti',['class'=>'btn btn-primary', 'style'=>'display: none', 'id'=>'submit'])}}

                    {{ Form::close() }}
                </div>
            </div>
        </section>
    </div>
    <script>
    
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
                    $('.photo_input').hide();
                    $('#name_revizija_input').hide();
                    $('#grouptitle_input').hide();
                    $('#submit').hide();
                    $('#DBpirmName_input').hide();
                }
                if ($('select[name=groupname]').val() == 'centrinis-biuras' || $('select[name=groupname]').val() == 'central-office') {
                    $('#name_input').show();
                    $('#duties_input').show();
                    $('#phone_input').show();
                    $('#email_input').show();
                    $('#infoText_input').show();
                    $('.photo_input').show();
                    $('#submit').show();

                    $('#name_short_input').hide();
                    $('#name_full_input').hide();
                    $('#address_input').hide();
                    $('#webpage_input').hide();
                    $('#members_input').hide();
                    $('#name_revizija_input').hide();
                    $('#grouptitle_input').hide();
                    $('#DBpirmName_input').hide();
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
                    $('.photo').hide();
                    $('#name_revizija_input').hide();
                    $('#grouptitle_input').hide();
                    $('#DBpirmName_input').hide();
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
                    $('.photo').hide();
                    $('#name_revizija_input').hide();
                    $('#grouptitle_input').hide();
                    $('#DBpirmName_input').hide();
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
                    $('.photo').hide();
                    $('#name_revizija_input').hide();
                    $('#grouptitle_input').hide();
                }

                if ($('select[name=groupname]').val() == 'parlamento-darbo-grupes') {
                    $('#DBpirmName_input').show();
                    $('#name_full_input').show();
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
                    $('.photo').hide();
                    $('#name_revizija_input').hide();
                    $('#grouptitle_input').hide();
                }

                if ($('select[name=groupname]').val() == 'padalinio-taryba' || $('select[name=groupname]').val() == 'padalinio-studentu-atstovai' || $('select[name=groupname]').val() ==
                        'padalinio-biuras' || $('select[name=groupname]').val() == 'padalinio-kuratoriai') {
                    $('#name_input').show();
                    $('#email_input').show();
                    $('#phone_input').show();
                    $('#lang_input').show();
                    $('#duties_input').show();
                    $('#photo_input').show();
                    $('#infoText_input').hide();
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
                    $('#name_full_input').show();
                    $('#phone_input').show();
                    $('#email_input').show();
                    $('#submit').show();

                    $('#name_short_input').hide();
                    $('#address_input').hide();
                    $('#webpage_input').hide();
                    $('#members_input').hide();
                    $('#duties_input').hide();
                    $('#infoText_input').hide();
                    $('.photo_input').hide();
                    $('#name_revizija_input').hide();
                    $('#grouptitle_input').hide();
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
                    $('.photo').hide();
                    $('#grouptitle_input').hide();
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
                    $('.photo').hide();
                    $('#grouptitle_input').hide();
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
                    $('.photo').hide();
                }

                if ($('select[name=groupname]').val() == 'aprasymas') {
                    $('#grouptitleDescription_input').show();
                    $('#infoText_input').show();
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
            $('.photo').hide();
            $('#name_revizija_input').hide();
            $('#grouptitle_input').hide();
            $('#submit').hide();
        }
        if ($('select[name=groupname]').val() == 'centrinis-biuras' || $('select[name=groupname]').val() == 'central-office') {
            $('#name_input').show();
            $('#duties_input').show();
            $('#phone_input').show();
            $('#email_input').show();
            $('#infoText_input').show();
            $('.photo').show();
            $('#submit').show();

            $('#name_short_input').hide();
            $('#name_full_input').hide();
            $('#address_input').hide();
            $('#webpage_input').hide();
            $('#members_input').hide();
            $('#name_revizija_input').hide();
            $('#grouptitle_input').hide();
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
            $('.photo').hide();
            $('#name_revizija_input').hide();
            $('#grouptitle_input').hide();
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
            $('.photo').hide();
            $('#name_revizija_input').hide();
            $('#grouptitle_input').hide();
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
            $('.photo').hide();
            $('#name_revizija_input').hide();
            $('#grouptitle_input').hide();
        }

        if ($('select[name=groupname]').val() == 'parlamento-darbo-grupes') {
            $('#DBpirmName_input').show();
            $('#name_full_input').show();
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
            $('.photo').hide();
            $('#name_revizija_input').hide();
            $('#grouptitle_input').hide();
        }

        if ($('select[name=groupname]').val() == 'padalinio-taryba' || $('select[name=groupname]').val() == 'padalinio-studentu-atstovai' || $('select[name=groupname]').val() == 'padalinio-biuras' ||
                $('select[name=groupname]').val() == 'padalinio-kuratoriai') {
            $('#name_input').show();
            $('#email_input').show();
            $('#phone_input').show();
            $('#duties_input').show();
            $('#photo_input').show();
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
            $('.photo').hide();
            $('#name_revizija_input').hide();
            $('#grouptitle_input').hide();
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
            $('.photo').hide();
            $('#grouptitle_input').hide();
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
            $('.photo').hide();
            $('#grouptitle_input').hide();
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
            $('.photo').hide();
        }

        if ($('select[name=groupname]').val() == 'aprasymas') {
            $('#grouptitleDescription_input').show();
            $('#infoText_input').show();
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

        $("#cropBtn").on('click', function () {
            $("#imageSRC").attr("src", document.getElementById("image").value);
        });

        var eyeCandy = $('#cropContainerEyecandy');
        var croppedOptions = {
            uploadUrl: 'upload',
            cropUrl: 'crop',
            cropData: {
                'width': eyeCandy.width(),
                'height': eyeCandy.height()
            }
        };
        var cropperBox = new Croppic('cropContainerEyecandy', croppedOptions);
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