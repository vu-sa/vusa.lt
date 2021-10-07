@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Redaguoti darbotvarės įrašą
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/darbotvarke/{id}/redaguoti' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li class="active">Redaguoti darbotvarės įrašą</li>': '' !!}
            </ol>
        </section>

        <section class="content">
            @if (count($errors) > 0)
                <div class="alert alert-danger" role="alert">
                    <ul>
                        <?php $errors = array_unique($errors->all());?>
                        @foreach ($errors as $error)
                            @if($error == 'validation.required')
                                <li>Laukai su * turi būti užpildyti</li>
                            @elseif($error == 'validation.unique')
                                <li>Įrašas su tokiu pavadinimus jau egzistuoja</li>
                            @else
                                <li>{{$error}}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="row ">
                <div class="col-md-12">
                    {!! Form::model($agenda, ['method' => 'PATCH']) !!}
                    <div class="form-group">
                        {{ Form::label('title', 'Pavadinimas *') }}
                        {{ Form::text('title', null, array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('date', 'Data *') }}
                        <br/>
                        {{-- {{ Form::selectYear('year', $date[0], date('Y')+1) }}:
                        {{ Form::selectMonth('month', $date[1]) }}:
                        {{ Form::selectYear('day', 1, 31, $date[2]) }}
                        {{ Form::hidden('date', null, array('class'=>'form-control', 'disabled'=>'disabled')) }} --}}
                        {{ Form::date('date', date("Y-m-d", strtotime($agenda['date'])   )) ;}}
                    </div>

                    <div class="form-group">
                        {{ Form::label('description', 'Aprašymas ') }}
                        {{ Form::textarea('description', null, array('class'=>'form-control edit')) }}
                    </div>

                    {{Form::submit('Atnaujinti',['class'=>'btn btn-primary'])}}

                    {{ Form::close() }}
                </div>
            </div>
        </section>
    </div>
@endsection