@extends('layouts.admin.resources.agenda')

@section('title', 'Atnaujinti darbotvarkės įrašą')

@section('formOpen')
    {{ Form::model($agendaEvent, ['method' => 'PATCH', 'route' => ['pages.admin.agenda.update', $agendaEvent->id]]) }}
@endsection

@section('formSubmit')
    {{ Form::submit('Atnaujinti', ['class' => 'btn btn-primary', 'id' => 'submit']) }}
@endsection