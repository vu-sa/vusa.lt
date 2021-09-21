@extends('layouts.user.master')

@if(Lang::locale() == 'lt')
    @section('title','Programų, klubų ir projektų kontaktai')
@else
    @section('title','Contacts for programs, clubs and projects')
@endif

@section('content')
    <div class="container">
        @if(Lang::locale() == 'lt')
            <div class="pageTitle">VU SA programų, klubų ir projektų kontaktai</div>
        @else
            <div class="pageTitle">VU SA Contacts for programs, clubs and projects</div>
        @endif
        @if($contactGroupDescription ?? '')
            <div style="padding-bottom: 20px;">
                {!! $contactGroupDescription !!}
            </div>
        @endif

        <div class="row contactRowTaryba">
            <?php $index = 0;?>
            @foreach($contacts as $contact)
                <div class="col-md-4 tarybaItem">
                    <?php
                    $index += 1;
                    $colorNumber = rand(1, 4);
                    switch ($colorNumber) {
                        case 1:
                            $color = '#bd2835';
                            break;
                        case 2:
                            $color = '#FBB03B';
                            break;
                        case 3:
                            $color = '#5D5D5D';
                            break;
                        case 4:
                            $color = '#000000';
                            break;
                    }
                    ?>
                    <div class="tarybaName">
                        <br/>
                        {{$contact->name_full}}
                    </div>
                    <div class="tarybaLine" style="background-color: {{$color}}"></div>

                    <div class="tarybaNameFull">
                        {{$contact->name}}<br/>
                        @if(Lang::locale() == 'lt')
                            Tel.
                        @else
                            Phone
                        @endif
                        {{$contact->phone}}<br/>
                        <a href="mailto:{{$contact->email}}">{{$contact->email}}</a><br>
                    </div>
                </div>
                {!! ($index % 3 === 0) ? '</div> <div class="row contactRowTaryba"> ':'' !!}
                {!! ($index == sizeof($contacts) ? '</div>':'') !!}
            @endforeach
        </div>
@endsection
