@extends('layouts.admin.resources.calendar')

@section('title', 'Sukurti naują kalendoriaus įrašą')

@section('formOpen')
    {{ Form::open(['route' => 'pages.admin.calendar.store']) }}
@endsection

@section('formSubmit')
    {{ Form::submit('Sukurti', ['class' => 'btn btn-primary', 'id' => 'submit']) }}
@endsection
