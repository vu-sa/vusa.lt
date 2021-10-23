@extends('layouts.admin.resources.banner')

@section('title', 'Pridėti reklaminį banerį')

@section('formOpen')
    {{ Form::open(['route' => ['pages.admin.banner.store'], 'files' => true]) }}
@endsection

@section('formSubmit')
    {{ Form::submit('Sukurti', ['class' => 'btn btn-primary', 'id' => 'submit']) }}
@endsection
