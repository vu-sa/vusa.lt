@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Redaguoti pagrindinio puslapio {{$elementInfo}} elementa
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/pagrindinis' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li class="active">Pridėti puslapį</li>': '' !!}
            </ol>
        </section>

        <section class="content">
            <div class="row ">
                <div class="col-md-12">
                    {{ Form::open() }}
                    <div class="form-group">
                        {{ Form::label('title', 'Tipas') }}
                        {{ Form::select('type',
                            array('naujiena' => 'Naujiena',
                                  'infoPage' => 'Info puslapis',
                                  'modulis' => 'Modulis'),
                                  'naujiena', array('class'=>'form-control')
                                  )}}
                    </div>
                    <div class="form-group">
                        Button with modal or tabs
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </section>
    </div>
@endsection