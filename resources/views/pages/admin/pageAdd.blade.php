@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Sukurti naują puslapį
                <br/>
                <small style="color: red">* - reikalingi laukai</small>
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/puslapiai/prideti' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li>Puslapiai</li> <li class="active">Pridėti puslapį</li>': '' !!}
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
                    {{ Form::open() }}
                    <div class="form-group">
                        {{ Form::label('title', 'Pavadinimas *') }}
                        {{ Form::text('title', '', array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('permalink', 'Nuoroda') }}
                        {{ Form::text('permalink', '', array('class'=>'form-control', 'readonly'=>'readonly')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('category', 'Kategorija *') }}
                        {{ Form::select('category', $pagesCatsShort, '', array('class'=>'form-control') )}}
                    </div>

                    <div class="form-group">
                        {{ Form::label('lang', 'Kalba *') }}
                        {{ Form::select('lang', array('lt' => 'LT', 'en' => 'EN'), '', array('class'=>'form-control') )}}
                    </div>

                    <div class="form-group" style="display: none" id="title_lt_input">
                        {{ Form::label('title_lt', 'Puslapis LT kalba *') }}
                        {{ Form::text('title_lt', '', array('class'=>'form-control') )}}
                    </div>

                    <div class="form-group" style="display: none" id="permalink_lt_input">
                        {{ Form::label('permalink_lt', 'Nuoroda į LT puslapį') }}
                        {{ Form::text('permalink_lt', '', array('class'=>'form-control', 'readonly'=>'readonly')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('text', 'Puslapio tekstas *') }}
                        {{ Form::textarea('text', '', array('class'=>'form-control edit')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('mainInfo', 'Papildoma informacija (punktus atskirti pliuso ženklu (+))') }}
                        {{ Form::textarea('mainInfo', null, array('class'=>'form-control edit')) }}
                    </div>

                    {{Form::submit('Sukurti',['class'=>'btn btn-primary'])}}

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
            $("#permalink").val('/' + InputTitle);
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
    </script>
@endsection