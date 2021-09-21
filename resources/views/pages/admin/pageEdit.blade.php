@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Atnaujinti puslapį
                <br/>
                <small style="color: red">* - reikalingi laukai</small>
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/puslapiai/{permalink}/redaguoti' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li>Puslapiai</li> <li class="active">Redaguoti puslapį</li>': '' !!}
            </ol>
        </section>

        <section class="content">
            @if (count($errors) > 0)
                <div class="alert alert-danger" role="alert">
                    <ul>
                        <?php $errors = array_unique($errors->all());?>
                        @foreach ($errors as $error)
                            @if($error == 'validation.required')
                                <li>Puslapio pavadinimo, kategorijos bei puslapio teksto laukai turi būti užpildyti.</li>
                            @elseif($error == 'validation.unique')
                                <li>Puslapis su tokiu pavadinimu jau egzistuoja.</li>
                            @else
                                <li>{{$error}}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="row">
                <div class="col-md-12">
                    {!! Form::model($pageInfo, ['method' => 'PATCH' ]) !!}

                    {{ Form::hidden('id', null, array('id' => 'id')) }}

                    <div class="form-group">
                        {{ Form::label('title', 'Pavadinimas *') }}
                        {{ Form::text('title', null, array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('permalink', 'Nuoroda') }}
                        {{ Form::text('permalink', null, array('class'=>'form-control', 'readonly'=>'readonly')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('category', 'Kategorija *') }}
                        {{ Form::select('category', $pagesCatsShort, null, array('class'=>'form-control') )}}
                    </div>

                    <div class="form-group">
                        {{ Form::label('lang', 'Kalba *') }}
                        {{ Form::select('lang', array('lt' => 'LT', 'en' => 'EN'), null, array('class'=>'form-control') )}}
                    </div>

                    <div class="form-group" style="display: none" id="title_lt_input">
                        {{ Form::label('title_lt', 'Puslapis LT kalba *') }}
                        {{ Form::text('title_lt', null, array('class'=>'form-control') )}}
                    </div>

                    <div class="form-group" style="display: none" id="permalink_lt_input">
                        {{ Form::label('permalink_lt', 'Nuoroda į LT puslapį') }}
                        {{ Form::text('permalink_lt', null, array('class'=>'form-control', 'readonly'=>'readonly')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('text', 'Puslapio turinys *') }}
                        {{ Form::textarea('text', null, array('class'=>'form-control edit')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('mainInfo', 'Pagrindinė informacija (punktus atskirti pliuso ženklo (+))') }}
                        {{ Form::textarea('mainInfo', null, array('class'=>'form-control edit')) }}
                    </div>

                    {{Form::submit('Atnaujinti',['class'=>'btn btn-primary'])}}
                    {{--<input id="saveContent" class="btn btn-primary" value="Išsaugoti">--}}

                    {{ Form::close() }}
                </div>
            </div>
        </section>
    </div>

    <script>
        $(document).ready(function () {
            $('select[name=lang]').change(function (e) {
                if ($('select[name=lang]').val() == 'lt') {
                    $('#title_lt_input').hide();
                    $('#permalink_lt_input').hide();
                }
                if ($('select[name=lang]').val() == 'en') {
                    $('#title_lt_input').show();
                    $('#permalink_lt_input').show();
                }
            });

            if ($('select[name=lang]').val() == 'en') {
                $('#title_lt_input').show();
                $('#permalink_lt_input').show();
            }
            if ($('select[name=lang]').val() == 'lt') {
                $('#title_lt_input').hide();
                $('#permalink_lt_input').hide();
            }
        });

        $("#title_lt").autocomplete({
            source: "/admin/puslapiai/pageName",
            minLength: 3,
            select: function (event, ui) {
                $('#title').val(ui.item.value);
            }
        });

        $("#title").focusout(function () {
            var InputTitle = $('#title').val().toLowerCase();
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
            InputTitle = InputTitle.replace(/[.,:"„”“?]/g, '');
            $("#permalink").val(InputTitle);
        });

        $("#title_lt").focusout(function () {
            var InputTitle = $('#title_lt').val().toLowerCase();
            InputTitle = InputTitle.replace(/[ą]/g, 'a');
            InputTitle = InputTitle.replace(/[č]/g, 'c');
            InputTitle = InputTitle.replace(/[ęė]/g, 'e');
            InputTitle = InputTitle.replace(/[į]/g, 'i');
            InputTitle = InputTitle.replace(/[š]/g, 's');
            InputTitle = InputTitle.replace(/[ūų]/g, 'u');
            InputTitle = InputTitle.replace(/[ž]/g, 'z');
            InputTitle = InputTitle.replace(/ /g, '-');
            InputTitle = InputTitle.replace(/[.,:"„”“?]/g, '');
            $("#permalink_lt").val(InputTitle);
        });

        $('#saveContent').on('click', function (e) {
//            $.post(window.location.pathname);
            var $form = $(this),
                    title = $form.find("input[name='title']").val(),
                    permalink = $form.find("input[name='permalink']").val(),
                    category = $form.find("input[name='category']").val(),
                    lang = $form.find("input[name='lang']").val(),
                    title_lt = $form.find("input[name='title_lt']").val(),
                    permalink_lt = $form.find("input[name='permalink_lt']").val(),
                    text = $form.find("input[name='text']").val(),
                    mainInfo = $form.find("input[name='mainInfo']").val(),
                    url = $form.attr("action");

            // Send the data using post
            $.post(url, {title: 'adawawdawd', permalink: 'adawawdawd', category: category, lang: lang, title_lt: title_lt, permalink_lt: permalink_lt, text: text, maininfo: mainInfo});
        });
    </script>
@endsection
