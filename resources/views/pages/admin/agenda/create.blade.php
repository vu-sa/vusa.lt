@extends('layouts.admin.resources.agenda')

@section('title', 'Sukurti naują darbotvarkės įrašą')

@section('formOpen')
    {{ Form::open(['route' => 'pages.admin.agenda.store']) }}
@endsection

@section('formSubmit')
    {{ Form::submit('Sukurti', ['class' => 'btn btn-primary', 'id' => 'submit']) }}
@endsection
