@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                @yield('title')
            </h1>
            <small style="color: red">* - reikalingi laukai</small>
        </section>

        <section class="content">
            @if (count($errors) > 0)
                <div class="alert alert-danger" role="alert">
                    <ul>
                        <?php $errors = array_unique($errors->all()); ?>
                        @foreach ($errors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="row">
                <div class="col-md-12">

                    @yield('formOpen')

                    <div class="form-group" id="name_input">

                        {{ Form::label('title', 'Pavadinimas *') }}
                        {{ Form::text('title', null, ['class' => 'form-control']) }}
                    </div>

                    <div class="form-group photo">
                        {{ Form::label('value', 'Paveikslėlis *') }}
                        <span class="input-group-btn">
                            <a id="lfm" data-input="value" class="btn btn-primary">
                                <i class="far fa-image"></i> Pasirinkti nuotrauką
                            </a>
                        </span>
                        {{ Form::text('value', null, ['class' => 'form-control', 'readonly' => 'readonly']) }}
                    </div>

                    <div class="form-group" id="name_input">
                        {{ Form::label('url', 'Nuoroda *') }}
                        {{ Form::text('url', null, ['class' => 'form-control']) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('hide', 'Ar nematomas?') }}
                        {{ Form::checkbox('hide', 1) }}
                    </div>

                    @yield('formSubmit')

                    {{ Form::close() }}
                </div>
            </div>
        </section>
    </div>

    @include('components.scripts.filemanager')
@endsection