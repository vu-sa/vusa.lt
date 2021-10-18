@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="#"><i class="fas fa-tachometer-alt"></i>Home</a></li>
                            {!! $currentRoute == 'admin' ? '<li class="breadcrumb-item active">Dashboard</li>' : '' !!}
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            @can('handleEnConfiguration', App\Models\Padalinys::class)
            <div class="col-md-12">

                <div>
                    @if (Session::has('message'))
                        <div class="alert alert-info" role="alert">{{ Session::get('message') }}</div>
                    @endif
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4>EN kalbos palaikymo įjungimas</h4>
                    </div>
                    <div class="card-body">
                        <p>Šis pasirinkimas leidžia įjungti anglų kalbos palaikymą padalinio puslapiui.</p>
                        <ul>
                            <li>Kai išjungta: EN veliavėlė meniu sekcijoje nukreips į VU SA pagrindinį EN puslapį.</li>
                            <li>Kai įjungta: EN veliavėlė meniu sekcijoje padalinio puslapyje nukreips į padalinio puslapio EN puslapį. Taip pat padalinys atsiras EN meniu juostoje.</li>
                        </ul>
        
                        {!! Form::model($padalinys, ['method' => 'PATCH' ]) !!}
                        <div class="form-group">
        
                            {{ Form::open() }}
                            {{ Form::label('en', 'Pasirinkti, ar įjungtas EN režimas') }}
                            {{ Form::checkbox('en', true) }}
        
                        </div>
                        {{ Form::submit('Atnaujinti', ['class' => 'btn btn-primary']) }}
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
            @endcan
        </section>
    </div>
@endsection
