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
                <div class="col-xs-8 col-md-4 infoCBinfo">
                    <div class="contactTopText">
                        <div class="contactText">
                            <br/>
                            @if($contactGroupDescription ?? '')
                                {!! $contactGroupDescription !!}
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-8 infoCBfoto hide_mob">
                    <div class="blackBackgroundContacts">
                        <div style="height: 375px;" class="layerCB">
                            <img class="contactTopFoto" src="{!! asset($contactGroupPhoto) !!}">
                        </div>
                    </div>
                </div>
            </div>

            <br class="hide_mob"/>
        @endif

        <?php $index = 0;?>
        @foreach($contacts as $contact)
            <?php $index += 1;?>
            {!!($index < 2 & $index % 3 == 1) ? '<div class="row contactRowBiuras"> ':'' !!}
            <div class="col-md-4">
                <div class="contactPerson">
                    <img class="contactFoto" src="{!! asset($contact->image) !!}">

                    <div class="{!! (strlen($contact->duties) > 47 ) ? 'sritiesInfoTwo':'sritiesInfoOne' !!} {!! (strlen($contact->infoText) < 200 ) ? 'contactInfoMiddle':'' !!}">
                        <p>{!! strip_tags($contact->infoText) !!}</p>
                    </div>
                    <div class="{!! (strlen($contact->duties) > 47) ? 'pareigosPlaceTwo':'pareigosPlaceOne' !!}">
                        <p>{{$contact->duties}}</p>
                    </div>
                </div>
                <div class="contactPersonInfo">
                    <div class="contactPersonName">{{$contact->name}}</div>
                    <div class="contactPersonContacts">
                        @if($contact->phone != '')
                        {{ __('Tel.') }} {{$contact->phone}} <br/>
                        @endif
                        <a href="mailto:{{$contact->email}}">{{$contact->email}}</a><br>
                    </div>
                </div>
            </div>
            {!!($index % 3 === 0) ? '</div> <div class="row contactRowBiuras"> ':'' !!}
            {!! ($index == sizeof($contacts) ? '</div>':'') !!}
        @endforeach
    </div>

@endsection