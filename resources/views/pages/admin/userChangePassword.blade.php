@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Redaguoti esamo naudotojo paskyros informaciją
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
            <div class="row">
                <div class="col-md-12">
                    {!! Form::model($userInfo, ['method' => 'PATCH',]) !!}
                    <div class="form-group">
                        {{ Form::label('username', 'Naudotojo vardas *') }}
                        {{ Form::text('username', null, array('class'=>'form-control','readonly'=>'readonly')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('realname', 'Vardas, pavardė *') }}
                        {{ Form::text('realname', null, array('class'=>'form-control','readonly'=>'readonly')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('password', 'Slaptažodis *') }}
                        {{ Form::password('password', array('class'=>'form-control','placeholder'=>'Naujas slaptaždodis')) }}
                        {{ Form::password('password_repeat', array('class'=>'form-control','placeholder'=>'Pakartokite naują slaptažodį')) }}
                    </div>

                    {{Form::submit('Atnaujinti informaciją',['class'=>'btn btn-primary'])}}

                    {{ Form::close() }}
                </div>
            </div>
        </section>
    </div>
@endsection