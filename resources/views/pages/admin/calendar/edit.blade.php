@extends('layouts.admin.resources.calendar')

@section('title', 'Atnaujinti kalendoriaus įrašą')

@section('formOpen')
    {{ Form::model($calendarEvent, ['method' => 'PATCH', 'route' => ['pages.admin.calendar.update', $calendarEvent->id]]) }}
@endsection

@section('formSubmit')
    {{ Form::submit('Atnaujinti', ['class' => 'btn btn-primary', 'id' => 'submit']) }}
@endsection