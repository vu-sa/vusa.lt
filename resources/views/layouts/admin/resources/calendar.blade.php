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

                    @yield('formOpen')

                    <div class="form-group">
                        {{ Form::label('title', 'Pavadinimas *') }}
                        {{ Form::text('title', NULL, array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('date', 'Data *') }}
                        <br/>
                        {{ Form::date('date', NULL, array('class'=>'form-control')); }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('category', 'Kategorija *') }}
                        {{ Form::select('category',
                            ['akadem' => 'Komisijos, atstovavimas ir akademiniai dalykai',
                                  'soc' => 'Socialiniai renginiai',
                                  'sventes' => 'Valstybinės šventės'],
                                  $category ?? 'akadem', array('class'=>'form-control')
                                  )}}
                    </div>

                    <div class="form-group">
                        {{ Form::label('descr', 'Aprašymas') }}
                        {{ Form::textarea('descr', NULL, array('class'=>'form-control edit')) }}
                    </div>

                    @yield('formSubmit')

                    {{ Form::close() }}
                </div>
            </div>
        </section>
    </div>
@endsection