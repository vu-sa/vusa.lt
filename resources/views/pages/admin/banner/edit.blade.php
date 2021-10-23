@extends('layouts.admin.resources.banner')

@section('title', 'Atnaujinti reklaminį banerį')

@section('formOpen')
    {{ Form::model($banner, ['method' => 'PATCH', 'route' => ['pages.admin.banner.update', $banner->id]]) }}
@endsection

@section('formSubmit')
    {{ Form::submit('Atnaujinti', ['class' => 'btn btn-primary', 'id' => 'submit']) }}
@endsection
