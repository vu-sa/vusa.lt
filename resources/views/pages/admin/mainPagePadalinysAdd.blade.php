@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                @if($position == 'bottomItem')
                    Pridėti apatinį elementą
                @elseif($position == 'sideItem')
                    Pridėti šoninį elementą
                @elseif($position == 'lowbottom')
                    Redaguoti aprašymą
                @endif
                <br/>
                <small style="color: red">* - reikalingi laukai</small>
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/pagrindinis/{groupAlias}/prideti' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li>Puslapiai</li> <li class="active">Pridėti elementą</li>': '' !!}
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
                    {{ Form::open() }}

                    @if($position == 'lowbottom')
                        <div class="form-group">
                            {{ Form::label('text', 'Tekstas *') }}
                            {{ Form::textarea('text', '', array('class'=>'form-control edit')) }}
                        </div>
                    @else
                        <div class="form-group">
                            {{ Form::label('type', 'Tipas *') }}
                            {{ Form::select('type', array('0' => '--- Parink tipą ---', 'link' => 'Nuoroda', 'news' => 'Naujiena', 'page' => 'Puslapis'), '0', array('class'=>'form-control'))}}
                        </div>

                        <div class="box" style="display: none" id="naujiena2_input">
                            <div class="box-header with-border">
                                <h3 class="box-title">Naujiena</h3>
                            </div>
                            <div class="box-body">
                                <div class="form-group">
                                    {{ Form::label('newsContent', 'Puslapių pasirinkimas *') }}
                                    {{ Form::select('newsContent[]', $newsContent, null, array('data-placeholder'=>'Įrašyk naujienos pavadinimą', 'class'=>'form-control select2','style'=>'width: 100%')
                                    ) }}
                                </div>
                            </div>
                        </div>

                        <div class="box" style="display: none" id="pages_input">
                            <div class="box-header with-border">
                                <h3 class="box-title">Puslapiai</h3>
                            </div>
                            <div class="box-body">
                                <div class="form-group">
                                    {{ Form::label('pageContent', 'Puslapių pasirinkimas *') }}
                                    {{ Form::select('pageContent[]', $pageContent, null, array('data-placeholder'=>'Įrašyk puslapių pavadinimus', 'class'=>'form-control select2', 'multiple'=>true,'style'=>'width: 100%')) }}
                                </div>
                            </div>
                        </div>

                        <div class="form-group" style="display: none" id="linkText_input">
                            {{ Form::label('text', 'Tekstas *') }}
                            {{ Form::text('text', '', array('class'=>'form-control')) }}
                        </div>

                        <div class="form-group" style="display: none" id="link_input">
                            {{ Form::label('link', 'Nuoroda') }}
                            {{ Form::text('link', '', array('class'=>'form-control')) }}
                        </div>

                        <div class="form-group" style="display: none" id="lang_input">
                            {{ Form::label('lang', 'Kalba') }}
                            {{ Form::select('lang', array('lt' => 'LT', 'en' => 'EN'), 'lt', array('class'=>'form-control') )}}
                        </div>
                    @endif

                    <div class="form-group">
                        {{ Form::hidden('position', $position, array('id' => 'type')) }}
                    </div>

                    {{Form::submit('Sukurti',['class'=>'btn btn-primary', 'id'=>'submit'])}}

                    {{ Form::close() }}
                </div>
            </div>
        </section>
    </div>
    <script>
        var position = new URL(window.location.href).searchParams.get("position");

        $("#text").autocomplete({
            source: "/admin/puslapiai/padalinys?position=" + position,
            minLength: 3,
            select: function (event, ui) {
                $('#title').val(ui.item.value);
            }
        });

        $(function () {
            $(".select2").select2();

            $(function () {
                $('input[type="checkbox"]').each(function () {
                    var self = $(this),
                            label = self.next(),
                            label_text = label.text();

                    label.remove();
                    self.iCheck({
                        checkboxClass: 'icheckbox_line-blue',
                        insert: '<div class="icheck_line-icon"></div>' + label_text
                    });
                });
            });
        });

        $('select[name=type]').change(function (e) {
            if ($('select[name=type]').val() == '0') {
                $('#naujiena2_input').hide();
                $('#pages_input').hide();
                $('#link_input').hide();
                $('#submit').hide();
                $('#lang_input').hide();
                $('#linkText_input').hide();
            }

            if ($('select[name=type]').val() == 'news') {
                $('#naujiena2_input').show();
                $('#submit').show();

                $('#pages_input').hide();
                $('#lang_input').hide();
                $('#link_input').hide();
                $('#linkText_input').hide();
            }

            if ($('select[name=type]').val() == 'page') {
                $('#pages_input').show();
                $('#submit').show();

                $('#naujiena2_input').hide();
                $('#lang_input').hide();
                $('#link_input').hide();
                $('#linkText_input').hide();
            }

            if ($('select[name=type]').val() == 'link') {
                $('#link_input').show();
                $('#lang_input').show();
                $('#linkText_input').show();
                $('#submit').show();

                $('#pages_input').hide();
                $('#naujiena2_input').hide();
            }
        });

        if ($('select[name=type]').val() == '0') {
            $('#naujiena2_input').hide();
            $('#pages_input').hide();
            $('#link_input').hide();
            $('#lang_input').hide();
            $('#submit').hide();
            $('#linkText_input').hide();
        }

        if ($('select[name=type]').val() == 'news') {
            $('#naujiena2_input').show();
            $('#submit').show();

            $('#pages_input').hide();
            $('#lang_input').hide();
            $('#link_input').hide();
            $('#linkText_input').hide();
        }

        if ($('select[name=type]').val() == 'page') {
            $('#pages_input').show();
            $('#submit').show();

            $('#naujiena2_input').hide();
            $('#lang_input').hide();
            $('#link_input').hide();
            $('#linkText_input').hide();
        }

        if ($('select[name=type]').val() == 'link') {
            $('#link_input').show();
            $('#linkText_input').show();
            $('#lang_input').show();
            $('#submit').show();

            $('#pages_input').hide();
            $('#naujiena2_input').hide();
        }
    </script>
@endsection