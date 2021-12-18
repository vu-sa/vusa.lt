@extends('layouts.user.master')

@section('title', $title)

@section('content')
    <div class="container">
        <div class="pageTitle">{{$title}}</div>
        
        @if (Lang::locale() == 'lt')
        <p><strong><a href="{{ 'http://' . request()->getHttpHost() . '/lt' }}"><< Grįžti į pradinį puslapį</a></strong></p>
        @endif
       
        <br>
        @if (strpos($title, 'koordinatoriai') !== false || strpos($title, 'biuras') !== false)
        <div class="row">
            <div class="col-lg-5 mb-sm-2 contactTopText">
                @if($contactGroupDescription ?? '')
                    {!! $contactGroupDescription !!}
                @endif
            </div>
            <div class="col-lg-7 mb-sm-2">
                <div class="blackBackgroundContacts">
                    <img class="contactTopFoto" src="{!! asset($contactGroupPhoto) !!}">
                </div>
            </div>
        </div>
        @endif

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
                        @if($contact->phone ?? '')
                        <p class="m-0">{{ __('Tel.') }} {{$contact->phone}}</p>
                        @endif
                    <p class="m-0"><a href="mailto:{{$contact->email}}">{{$contact->email}}</a></p>
                    </div>
                </div>
            </div>
            {!!($index % 3 === 0) ? '</div> <div class="row"> ':'' !!}
            {!! ($index == sizeof($contacts) ? '</div>':'') !!}
        @endforeach
    </div>

@endsection