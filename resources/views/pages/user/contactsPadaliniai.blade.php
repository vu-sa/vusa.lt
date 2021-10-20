@extends('layouts.user.master')

@if(Lang::locale() == 'lt')
    @section('title', 'Padalinių kontaktai')
@else
    @section('title', 'Units contacts')
@endif

@section('content')

    <div class="container">
        @if(Lang::locale() == 'lt')
            <div class="pageTitle">VU SA padalinių fakultetuose kontaktai</div>
        @else
            <div class="pageTitle">VU SA padalinių fakultetuose contacts</div>
        @endif
        <?php $index = 1;?>
        <div class="row contactRowTaryba">
            @foreach($contacts as $contact)
                <div class="col-md-4 tarybaItem">
                    <?php
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
                        {{$contact->name_short}}
                    </div>
                    <div class="tarybaLine" style="background-color: {{$color}}"></div>

                    <div class="padalinysNameFull">
                        {{$contact->name_full}}
                    </div>

                    <div class="padalinysInfo">
                        {{$contact->address}}<br/>
                        @if(Lang::locale() == 'lt')
                        {{ __('Tel.') }}
                        @else
                            Phone
                        @endif
                        {{$contact->phone}}<br/>
                        <a href="mailto:{{$contact->email}}">{{$contact->email}}</a><br>
                        @if(strlen($contact->webpage) > 0)
                            @if(strpos($contact->webpage, 'http://') !== false)
                                <a href="{{$contact->webpage}}" target="_blank" rel="noopener">{{$contact->webpage}}</a>
                            @else
                                <a href="http://{{$contact->webpage}}" target="_blank" rel="noopener">{{$contact->webpage}}</a>
                            @endif
                        @endif
                    </div>
                </div>
                {!!($index % 3 == 0) ? '</div> <div class="row contactRowTaryba"> ':'' !!}
                {!! ($index == sizeof($contacts) ? '</div>':'') !!}
                <?php $index += 1; ?>
            @endforeach
        </div>
    </div>
@endsection
