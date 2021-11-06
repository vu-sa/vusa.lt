@extends('layouts.user.master')

@section('title','Centrinio biuro kontaktai')

@section('content')
    <div class="container">
        <div class="pageTitle">Centrinis biuras</div>
        <div class="row">
            <div class="col-lg-5 mb-sm-2 contactTopText">
                @if($contactGroupDescription ?? '')
                    {!! $contactGroupDescription !!}
                @endif
            </div>
            <div class="col-lg-7 mb-sm-2">
                <div class="blackBackgroundContacts">
                    <img class="contactTopFoto" src="{!! asset('images/photos/observatorijos_kiemelis.jpg') !!}">
                </div>
            </div>
        </div>

        @php
        $index = 0;
        @endphp

        @foreach($contacts as $contact)
            @php
            $index += 1;
            @endphp

            {!! ($index < 2 & $index % 3 == 1) ? '<div class="row"> ' : '' !!}

            <div class="col-lg-4">
                <div class="contactPerson">
                    <img class="contactFoto" src="{!! asset($contact->image) !!}">

                    <div class="contactInfoMiddle">
                        {!! strip_tags($contact->infoText) !!}
                    </div>
                    <div class="pareigosPlace">
                        {{$contact->duties}}
                    </div>
                </div>
                <div class="contactPersonInfo">
                    <div class="contactPersonName">{{$contact->name}}</div>
                    <div class="contactPersonContacts">
                        <p class="m-0">{{ __('Tel.') }} {{$contact->phone}}</p>
                    <p class="m-0"><a href="mailto:{{$contact->email}}">{{$contact->email}}</a></p>
                    </div>
                </div>
            </div>
            {!!($index % 3 === 0) ? '</div> <div class="row"> ':'' !!}
            {!! ($index == sizeof($contacts) ? '</div>':'') !!}
        @endforeach
    </div>

@endsection