@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Atnaujinti atsiskaitymą
            </h1>
            <small style="color: red">* - reikalingi laukai</small>
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
                    {!! Form::model($atsiskaitymas, ['method' => 'PATCH', 'route' => ['pages.admin.exam.update', $atsiskaitymas->id]]) !!}

                    {{-- {{ Form::hidden('page', $_GET["page"], array('class'=>'form-control')) }} --}}

                    <div class="form-group">
                        {{ Form::label('name', 'Vardas ir pavardė') }}
                        {{ Form::text('name', null, array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('contact', 'El. paštas') }}
                        {{ Form::text('contact', null, array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('phone', 'Telefono numeris') }}
                        {{ Form::text('phone', null, array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('exam', 'Atsiskaitymo pobūdis') }}
                        {{ Form::select('exam', array('-'=>'-- Pobūdis --','koliokviumas'=>'Koliokviumas','egzaminas' => 'Egzaminas'), null, array('class'=>'form-control'))}}
                    </div>

                    <div class="form-group">
                        {{ Form::label('padalinys', 'Atsiskaitymą laikančiųjų padalinys') }}
                        {{ Form::select('padalinys', array('-'=>'-- Padalinys --','chgf'=>'ChGF','evaf' => 'EVAF', 'ff'=>'ff', 'filf'=>'FiLF', 'fsf'=>'FsF', 'gmc'=>'GMC', 'if'=>'IF', 'kf'=>'KF', 'knf'=> 'KnF', 'mf'=> 'MF',
                        'mif'=>'MIF', 'sa'=>'ŠA', 'tf'=> 'TF', 'tspmi'=> 'TSPMI', 'vm'=> 'VM'), null, array('class'=>'form-control'))}}
                    </div>

                    <div class="form-group">
                        {{ Form::label('place', 'Atsiskaitymo vieta: padalinys ir auditorija') }}
                        {{ Form::text('place', null, array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('time', 'Atsiskaitymo data, laikas') }}
                        {{ Form::text('time', null, array('class'=>'form-control', 'id'=>'time')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('duration', 'Atsiskaitymo trukmė (jei laikoma srautais, parašyti srautų skaičių ir kiek laiko skiriama vienam srautui)') }}
                        {{ Form::text('duration', null, array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('subject_name', 'Atsiskaitymo dalyko pavadinimas') }}
                        {{ Form::text('subject_name', null, array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('count', 'Atsiskaitymą laikančiųjų studentų skaičius') }}
                        {{ Form::number('count', null, array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('students_need', 'Reikalingas stebėtojų skaičius *') }}
                        {{ Form::text('students_need', null, array('class'=>'form-control')) }}
                    </div>

                    {{ Form::submit('Atnaujinti',['class'=>'btn btn-primary']) }}
                    {{ Form::close() }}
                </div>
            </div>
        </section>
    </div>
@endsection
