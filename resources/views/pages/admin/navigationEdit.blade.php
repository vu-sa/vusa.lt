@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Atnaujinti tinklapio navigacijos elementą
                <br/>
                <small style="color: red">* - reikalingi laukai</small>
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

            <div class="row ">
                <div class="col-md-12">
                    {!! Form::model($navInfo, ['method' => 'PATCH' ]) !!}
                    <div class="form-group">
                        {{ Form::label('text', 'Pavadinimas *') }}
                        {{ Form::text('text', null, array('class'=>'form-control dropdown')) }}
                        {{ Form::hidden('id', null, array('class'=>'form-control')) }}
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
                        {{ Form::text('url', null, array('class'=>'form-control')) }}
                        {{ Form::hidden('creator', $sessionInfo->id, array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('lang', 'Kalba *') }}
                        {{ Form::select('lang', array('lt' => 'LT', 'en' => 'EN'), null, array('class'=>'form-control'))}}
                    </div>

                    <div class="form-group">
                        {{ Form::label('pid', 'Kam priklauso *') }}
                        @if($arraySize == 1)
                            {{ Form::select('pid', $parentCat1, null, array('class'=>'form-control'))}}
                            <select class="form-control" name="pid2" id="pid2">
                                <option value=""></option>
                            </select>
                            <select class="form-control" name="pid3" id="pid3">
                                <option value=""></option>
                            </select>
                        @endif

                        @if($arraySize === 2)
                            {{ Form::select('pid', $parentCat1, null, array('class'=>'form-control'))}}
                            <select class="form-control" name="pid2" id="pid2">
                                <option value=""></option>
                            </select>
                            <select class="form-control" name="pid3" id="pid3">
                                <option value=""></option>
                            </select>
                        @endif

                        @if($arraySize === 3)
                            {{ Form::select('pid', $parentCat1, null, array('class'=>'form-control'))}}
                            <select class="form-control" name="pid2" id="pid2">
                                @foreach($parentCat2 as $parent)
                                    <option {!! ($parentId2 == $parent['id'] ? 'selected="selected"': "") !!} value="{{$parent['id']}}">
                                        {{$parent['text']}}
                                    </option>
                                @endforeach
                            </select>
                            <select class="form-control" name="pid3" id="pid3">
                                <option value=""></option>
                            </select>
                        @endif
                        @if($arraySize === 4)
                            {{ Form::select('pid', $parentCat1, null, array('class'=>'form-control'))}}
                            <select class="form-control" name="pid2" id="pid2">
                                @foreach($parentCat3 as $parent)
                                    <option {!! ($parentId3 == $parent['id'] ? 'selected="selected"': "") !!} value="{{$parent['id']}}">
                                        {{$parent['text']}}
                                    </option>
                                @endforeach
                            </select>
                            <select class="form-control" name="pid3" id="pid3">
                                @foreach($parentCat2 as $parent)
                                    <option {!! ($parentId2 == $parent['id'] ? 'selected="selected"': "") !!} value="{{$parent['id']}}">
                                        {{$parent['text']}}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <div class="form-group">
                        {{ Form::label('show', 'Ar matomas?') }}
                        {{ Form::checkbox('show', null, true) }}
                    </div>

                    {{Form::submit('Atnaujinti',['class'=>'btn btn-primary'])}}

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