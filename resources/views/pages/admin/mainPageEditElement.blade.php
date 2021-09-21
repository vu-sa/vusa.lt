@extends('layouts.admin.master')

@section('content')

    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Redaguoti pagrindinio puslapio {{$elementInfo['id']}} elementą
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/pagrindinis' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li class="active">Pridėti puslapį</li>': '' !!}
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
                    {!! Form::model($elementInfo, ['method' => 'PATCH' ]) !!}
                    {{ Form::hidden('id', $id, array('class'=>'form-control')) }}
                    <div class="form-group">
                        {{ Form::label('type', 'Tipas') }}
                        {{ Form::select('type',
                            array('0'=>'--- Parink grupę ---',
                                  'naujiena' => 'Naujiena',
                                  'infoPage' => 'Info puslapis',
                                  'modulis' => 'Modulis'),
                                  null, array('class'=>'form-control')
                                  )
                        }}
                    </div>

                    <div class="form-group" style="display: none" id="moduleName2">
                        {{ Form::label('moduleName', 'Modulis') }}
                        {{ Form::select('moduleName',
                            array(''=>'--- Pasirink modulį ---',
                                  'calendar'=>'Kalendorius',
                                  'links'=>'Nuorodos',
                                  'pollForm'=>'Apklausa'),
                                  null, array('class'=>'form-control')
                                  )
                        }}
                    </div>

                    {{--naujiena,puslapis--}}
                    <div class="form-group" style="display: none" id="text2">
                        {{ Form::label('text', 'Pavadinimas') }}
                        {{ Form::text('text', null, array('class'=>'form-control')) }}
                    </div>

                    <script>
                        $("#text").focusout(function () {
                            var InputTitle = $('#text').val().toLowerCase();
                            InputTitle = InputTitle.replace(/[ą]/g, 'a');
                            InputTitle = InputTitle.replace(/[č]/g, 'c');
                            InputTitle = InputTitle.replace(/[ę]/g, 'e');
                            InputTitle = InputTitle.replace(/[ė]/g, 'e');
                            InputTitle = InputTitle.replace(/[į]/g, 'i');
                            InputTitle = InputTitle.replace(/[š]/g, 's');
                            InputTitle = InputTitle.replace(/[ų]/g, 'u');
                            InputTitle = InputTitle.replace(/[ū]/g, 'u');
                            InputTitle = InputTitle.replace(/[ž]/g, 'z');
                            InputTitle = InputTitle.replace(/ /g, '-');
                            InputTitle = InputTitle.replace(/[,:"„”]/g, '');
                            $("#link").val('/' + InputTitle);
                        });
                    </script>

                    {{--Naujiena, puslapis--}}
                    <div class="form-group" style="display: none" id="link2">
                        {{ Form::label('link', 'Nuoroda *') }}
                        {{ Form::text('link', null, array('class'=>'form-control')) }}
                    </div>

                    {{--puslapis--}}
                    <div class="form-group" style="display: none" id="image2">
                        {{ Form::label('image', 'Iliustracija pagrindiniam puslapiui *') }}
                        <span class="input-group-btn">
                            <a id="lfm" data-input="image" class="btn btn-primary">
                              <i class="far fa-image"></i> Pasirinkti nuotrauką
                            </a>
                          </span>
                        {{ Form::text('image', '', array('class'=>'form-control', 'readonly'=>'readonly')) }}
                    </div>

                    {{Form::submit('Atnaujinti',['class'=>'btn btn-primary', 'style'=>'display: none', 'id'=>'submit'])}}

                    {{ Form::close() }}
                </div>
            </div>
        </section>
    </div>
    <script>

        $(function () {
            $('select[name=type]').change(function (e) {
                if ($('select[name=type]').val() == '0') {
                    $('#link2').hide();
                    $('#image2').hide();
                    $('#text2').hide();
                    $('#submit').hide();
                    $('#moduleName2').hide();
                }

                if ($('select[name=type]').val() == 'naujiena') {
                    $('#submit').show();
                    $('#text2').show();

                    $('#link2').hide();
                    $('#image2').hide();
                    $('#moduleName2').hide();
                }

                if ($('select[name=type]').val() == 'infoPage') {
                    $('#link2').show();
                    $('#image2').show();
                    $('#text2').show();
                    $('#submit').show();

                    $('#moduleName2').hide();
                }

                if ($('select[name=type]').val() == 'modulis') {
                    $('#submit').show();
                    $('#moduleName2').show();

                    $('#link2').hide();
                    $('#image2').hide();
                    $('#text2').hide();
                }
            });
            if ($('select[name=type]').val() == '0') {
                $('#link2').hide();
                $('#image2').hide();
                $('#text2').hide();
                $('#submit').hide();
                $('#moduleName2').hide();
            }

            if ($('select[name=type]').val() == 'naujiena') {
                $('#submit').show();
                $('#text2').show();

                $('#link2').hide();
                $('#image2').hide();
                $('#moduleName2').hide();
            }

            if ($('select[name=type]').val() == 'infoPage') {
                $('#link2').show();
                $('#image2').show();
                $('#text2').show();
                $('#submit').show();

                $('#moduleName2').hide();
            }

            if ($('select[name=type]').val() == 'modulis') {
                $('#submit').show();
                $('#moduleName2').show();

                $('#link2').hide();
                $('#image2').hide();
                $('#text2').hide();
            }
            
            $("#text").autocomplete({
                source: "/admin/naujienos/newsName",
                minLength: 3,
                select: function (event, ui) {
                    $('#title').val(ui.item.value);
                }
            });
 
        });
    </script>
@endsection