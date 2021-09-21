@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Sukurti naują vartotojo paskyrą
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/prideti_vartotoja' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li class="active">Pridėti puslapį</li>': '' !!}
            </ol>
        </section>

        <section class="content">
            <div class="row ">
                <div class="col-md-12">
                    {{ Form::open() }}
                    <div class="form-group">
                        {{ Form::label('username', 'Naudotojo vardas') }}
                        {{ Form::text('username', '', array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('realname', 'Vardas, pavardė') }}
                        {{ Form::text('realname', '', array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('password', 'Slaptažodis') }}
                        {{ Form::text('password', '', array('class'=>'form-control','placeholder'=>'Jūsų slaptaždodis')) }}
                        {{ Form::text('password_repeat', '', array('class'=>'form-control','placeholder'=>'Pakartokite slaptažodį')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('gid', 'Naudotojo grupė') }}
                        {{ Form::select('gid',
                            array('1' => 'Administratoriai',
                                  '2' => 'Pildo savo darbotvarkes',
                                  '3' => 'Pildo visų darbotvarkes ir kalendorių'),
                                  '1', array('class'=>'form-control')
                                  )}}
                    </div>

                    <div class="form-group ">
                        {{ Form::label('disabled', 'Naudotojas laikinai išjungtas') }}
                        {{ Form::checkbox('disabled', 'Taip')}}
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </section>
    </div>
@endsection