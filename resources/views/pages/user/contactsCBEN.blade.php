@extends('layouts.user.master')

@section('title','Contacts')

@section('content')
    <div class="container">
        <div class="pageTitle">Contacts</div>
        <div class="row">
            <div class="col-xs-8 col-md-4 infoCBinfo">
                <div class="contactTopText">
                    <div class="contactText">
                        <br/>
                        @if($contactGroupDescription != '')
                            {!! $contactGroupDescription !!}
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-8 infoCBfoto hide_mob">
                <div class="blackBackgroundContacts">
                    <div style="height: 375px;" class="layerCB">
                        <img class="contactTopFoto" src="{!! asset('images/observatorijos_kiemelis.jpg') !!}">
                    </div>
                </div>
            </div>
        </div>

        <br class="hide_mob"/>
        <?php $index = 0;?>
        @foreach($contacts as $contact)
            <?php $index += 1;?>
            {!!($index < 2 & $index % 3 == 1) ? '<div class="row contactRowBiuras"> ':'' !!}
            <div class="col-md-4">
                <div class="contactPerson">
                    <img class="contactFoto" src="{!! asset($contact->image) !!}">

                    <div class="{!! (strlen($contact->duties) > 50 ) ? 'sritiesInfoTwo':'sritiesInfoOne' !!} {!! (strlen($contact->infoText) < 200 ) ? 'contactInfoMiddle':'' !!}">
                        <span>{{strip_tags($contact->infoText)}}</span>
                    </div>
                    <div class="{!! (strlen($contact->duties) > 35 ) ? 'pareigosPlaceTwo':'pareigosPlaceOne' !!}">
                        <span>{{$contact->duties}}</span>
                    </div>
                </div>
                <div class="contactPersonInfo">
                    <div class="contactPersonName">{{$contact->name}}</div>
                    <div class="contactPersonContacts">
                        Phone {{$contact->phone}} <br/>
                        <a href="mailto:{{$contact->email}}">{{$contact->email}}</a><br>
                    </div>
                </div>
            </div>
            {!!($index % 3 === 0) ? '</div> <div class="row contactRowBiuras"> ':'' !!}
            {!! ($index == sizeof($contacts) ? '</div>':'') !!}
        @endforeach
    </div>
@endsection