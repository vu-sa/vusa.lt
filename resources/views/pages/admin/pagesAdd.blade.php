@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Sukurti naują puslapį
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/prideti_puslapi' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li class="active">Pridėti puslapį</li>': '' !!}
            </ol>
        </section>

        <section class="content">
            <div class="row ">
                <div class="col-md-12">
                    {{ Form::open() }}
                    <div class="form-group">
                        {{ Form::label('title', 'Pavadinimas') }}
                        {{ Form::text('title', '', array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('link', 'Nuoroda (įrašyti tik, jei į išorinį tinklapį (ne vusa.lt))') }}
                        {{ Form::text('link', '', array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('category', 'Naujienos kategorija') }}
                        {{ Form::select('category',
                            array('socakadem' => 'Socialinės - akademinės naujienos',
                                  'naujiena' => 'Naujiena',
                                  'info puslapis' => 'Puslapis su pastovia informacija',
                                  'vusa' => 'VU SA veikla, pozicija, dokumentai, ataskaitos',
                                  'kita' => 'Kita'),
                                  'naujiena', array('class'=>'form-control')
                                  )}}
                    </div>

                    <div class="form-group">
                        {{ Form::label('isImportant', 'Naujiena svarbi') }}
                        {{ Form::checkbox('isImportant', 'Taip')}}
                    </div>

                    <div class="form-group">
                        {{ Form::label('introText', 'Įvadinis tekstas') }}
                        {{ Form::textarea('introText', '', array('class'=>'form-control edit')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('fullText', 'Visas naujienos tekstas') }}
                        {{ Form::textarea('fullText', '', array('class'=>'form-control edit')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('iamge', 'Nuotrauka') }}
                        {{ Form::file('image') }}
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </section>
    </div>
@endsection