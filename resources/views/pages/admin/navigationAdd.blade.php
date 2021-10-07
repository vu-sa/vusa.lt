@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Pridėti naują tinklapio navigacijos elementą
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/prideti_vartotoja' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li class="active">Pridėti puslapį</li>': '' !!}
            </ol>
        </section>

        <section class="content">
            @if (count($errors) > 0)
                <div class="alert alert-danger" role="alert">
                    <ul>
                        <?php $errors = array_unique($errors->all());?>
                        @foreach ($errors as $error)
                            @if($error == 'validation.required')
                                <li>Pavadinimo, nuorodos ir kam priklauso laukai turi būti užpildyti</li>
                            @elseif($error == 'validation.unique')
                                <li>Navigacijos elementas su tokiu pavadinimu jau egzistuoja</li>
                            @else
                                <li>{{$error}}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row">
                <div class="col-md-12">
                    {!! Form::open() !!}
                    <div class="form-group">
                        {{ Form::label('text', 'Pavadinimas *') }}
                        {{ Form::text('text', '', array('class'=>'form-control dropdown')) }}
                    </div>

                    <script>
                        $("#text").focusout(function () {
                            var InputTitle = $('#text').val().toLowerCase();
                            InputTitle = InputTitle.replace(/[ą]/g, 'a');
                            InputTitle = InputTitle.replace(/[č]/g, 'c');
                            InputTitle = InputTitle.replace(/[ėę]/g, 'e');
                            InputTitle = InputTitle.replace(/[į]/g, 'i');
                            InputTitle = InputTitle.replace(/[š]/g, 's');
                            InputTitle = InputTitle.replace(/[ūų]/g, 'u');
                            InputTitle = InputTitle.replace(/[ž]/g, 'z');
                            InputTitle = InputTitle.replace(/ /g, '-');
                            InputTitle = InputTitle.replace(/[,:"„”]/g, '');
                            $("#url").val('/' + InputTitle);
                        });
                    </script>

                    <div class="form-group">
                        {{ Form::label('url', 'Nuoroda (įrašyti, jei į kitą tinklapį) *') }}
                        {{ Form::text('url', '', array('class'=>'form-control')) }}
                        {{ Form::hidden('creator', $sessionInfo->id, array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('lang', 'Kalba *') }}
                        {{ Form::select('lang', array('lt' => 'LT', 'en' => 'EN'), '', array('class'=>'form-control'))}}
                    </div>

                    <div class="form-group">
                        {{ Form::label('pid', 'Kam priklauso *') }}
                        {{ Form::select('pid', $parrentCats, '', array('class'=>'form-control'))}}
                        <select class="form-control" name="pid2" id="pid2">
                            <option value=""></option>
                        </select>
                        <select class="form-control" name="pid3" id="pid3">
                            <option value=""></option>
                        </select>
                    </div>

                    <div class="form-group">
                        {{ Form::label('show', 'Ar matomas?') }}
                        {{ Form::checkbox('show', '1', true) }}
                    </div>

                    {{Form::submit('Sukurti',['class'=>'btn btn-primary'])}}

                    {{ Form::close() }}
                </div>
            </div>
        </section>
    </div>

    <script>
        $("#text").autocomplete({
            source: "/admin/puslapiai/pageName",
            minLength: 3,
            select: function (event, ui) {
                $('#title').val(ui.item.value);
            }
        });

        $('#pid').on('change', function (e) {
            var cat_id = e.target.value;
            $.get('/admin/navigacija/subcats?cat_id=' + cat_id, function (data) {
                data.unshift({text: '    '});
                $('#pid2').empty();
                $.each(data, function (index, subcatObj) {
                    $('#pid2').append('<option value="' + subcatObj.id + '">' + subcatObj.text + '</option>');
                });
            })
        });
        $('#pid2').on('change', function (e) {
            var cat_id = e.target.value;
            $.get('/admin/navigacija/subcats?cat_id=' + cat_id, function (data) {
                data.unshift({text: '    '});
                $('#pid3').empty();
                $.each(data, function (index, subcatObj) {
                    $('#pid3').append('<option value="' + subcatObj.id + '">' + subcatObj.text + '</option>');
                });
            })
        });
    </script>
@endsection