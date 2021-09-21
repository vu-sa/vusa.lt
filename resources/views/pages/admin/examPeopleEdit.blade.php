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
                    {!! Form::model($zmogus, ['method' => 'PATCH' ]) !!}

                    {{ Form::hidden('page', $_GET["page"], array('class'=>'form-control')) }}

                    <div class="form-group">
                        {{ Form::label('subject_name', 'Pavadinimas *') }}
                        {{ Form::text('subject_name', null, array('class'=>'form-control','readonly'=>'readonly')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('name_p', 'Vardas, pavardė') }}
                        {{ Form::text('name_p', null, array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('contact_p', 'Kontaktinė informacija') }}
                        {{ Form::text('contact_p', null, array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('status_p', 'Statusas') }}
                        {{ Form::select('status_p', array('atvyko'=>'Atvyko','neatvyko' => 'Neatvyko'), null , array('class'=>'form-control'))}}
                    </div>

                    <div class="form-group">
                        {{ Form::label('padalinys_p', 'Padalinys') }}
                        {{ Form::text('padalinys_p', null, array('class'=>'form-control')) }}
                    </div>

                    {{Form::submit('Atnaujinti',['class'=>'btn btn-primary'])}}

                    {{ Form::close() }}
                </div>
            </div>
        </section>
    </div>
@endsection
