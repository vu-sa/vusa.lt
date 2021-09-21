@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Sukurti naują kalendoriaus įrašą
                <br/>
                <small style="color: red">* - reikalingi laukai</small>
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/kalendorius/prideti' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li class="active">Pridėti kalendoriaus įrašą</li>': '' !!}
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
                    {{ Form::open() }}
                    <div class="form-group">
                        {{ Form::label('title', 'Pavadinimas *') }}
                        {{ Form::text('title', '', array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('date', 'Data *') }}
                        <br/>
                        {{-- {{ Form::selectYear('year', date('Y'), date('Y')+1) }}:
                        {{ Form::selectMonth('month', date('m')) }}:
                        {{ Form::selectYear('day', 1, 31, date('d')) }}
                        {{ Form::hidden('date', '', array('class'=>'form-control', 'disabled'=>'disabled')) }} --}}
                        {{ Form::date('date', \Carbon\Carbon::now()); }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('category', 'Kategorija *') }}
                        {{ Form::select('category',
                            array('0' => '--- Pasirink kategoriją ---',
                                  'akadem' => 'Komisijos, atstovavimas ir akademiniai dalykai',
                                  'soc' => 'Socialiniai renginiai',
                                  'sventes' => 'Valtybinės šventės'),
                                  '0', array('class'=>'form-control')
                                  )}}
                    </div>

                    <div class="form-group">
                        {{ Form::label('descr', 'Aprašymas *') }}
                        {{ Form::textarea('descr', '', array('class'=>'form-control edit')) }}
                    </div>

                    {{Form::submit('Sukurti',['class'=>'btn btn-primary'])}}

                    {{ Form::close() }}
                </div>
            </div>
        </section>
    </div>
@endsection