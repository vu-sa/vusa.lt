@extends('layouts.user.master')

@if (Lang::locale() == 'lt')
    @section('title', 'Parlamento kontaktai')
    @else
    @section('title', 'Parliament contacts')
    @endif

    @section('content')
        <div class="container">

            @if (Lang::locale() == 'lt')
                <div class="pageTitle">VU SA Parlamentas</div>
            @else
                <div class="pageTitle">VU SA Parlament</div>
            @endif
            @if ($contactGroupDescription != '')
                <div style="padding-bottom: 20px;">
                    {!! $contactGroupDescription !!}
                </div>
            @endif

            <div class="row contactRowPadaliniai">
                <div class="col-md-4 parlamentasItem">
                    <div class="parlamentasName">
                        <?php $name = explode(' ', $parl_pirm->name); ?>
                        @if (substr($name[0], -2) == 'as' || substr($name[0], -2) == 'us')
                            @if (Lang::locale() == 'lt')
                                VU SA Parlamento<br />pirmininkas
                            @else
                                VU SA Parliament<br />chairman
                            @endif
                        @else
                            @if (Lang::locale() == 'lt')
                                VU SA Parlamento<br />pirmininkė
                                {{ $parl_pirm->name }}
                            @else
                                VU SA Parliament<br />chairwoman
                            @endif
                        @endif

                    </div>
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
                    <div class="parlamentasLine" style="background: {{ $color }};"></div>

                    <div class="padalinysInfo">
                        {{ $parl_pirm->name }}<br />
                        @if (Lang::locale() == 'lt')
                            Tel.
                        @else
                            Phone
                        @endif
                        {{ $parl_pirm->phone }}<br />
                        <a href="mailto:{{ $parl_pirm->email }}">{{ $parl_pirm->email }}</a><br />
                    </div>
                </div>
                <?php $index = 0; ?>
                @foreach ($contacts as $contact)
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
                    <div class="col-md-4 parlamentasItem">
                        <div class="parlamentasName">
                            <br />
                            {{ $contact->name_short }}
                        </div>
                        <div class="parlamentasLine" style="background: {{ $color }};"></div>

                        <div class="padalinysNameFull">
                            {{ $contact->name_full }}
                        </div>

                        <div class="padalinysInfo">
                            <?php $members = explode(',', $contact->members); ?>
                            @if (sizeof($members) == 3)
                                {{ $members[0] }}<br />
                                {{ $members[1] }}<br />
                                {{ $members[2] }}
                            @elseif(sizeof($members) == 4)
                                {{ $members[0] }}<br />
                                {{ $members[1] }}<br />
                                {{ $members[2] }}<br />
                                {{ $members[3] }}
                            @else
                                {{ $members[0] }}
                            @endif
                        </div>
                    </div>

                @endforeach
            </div>

            @if (sizeof($parlDBs) > 0)

                @if (Lang::locale() == 'lt')
                    <div class="pageTitle">Darbo grupės</div>
                @else
                    <div class="pageTitle">Work groups</div>
                @endif
                <div class="row contactRowPadaliniai">
                    <?php $index = 0; ?>
                    @foreach ($parlDBs as $parlDB)

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

                        <div class="col-md-4 parlamentasItem">
                            <div class="parlamentasName" style="text-decoration: underline">
                                <br />
                                {{ $parlDB->name_full }}
                            </div>
                            <div class="parlamentasLine" style="background: {{ $color }};"></div>

                            <div class="padalinysInfo">
                                {{ $parlDB->name }}<br />
                                {{ $parlDB->phone }}<br />
                                <a href="mailto:{{ $parlDB->email }}">{{ $parlDB->email }}</a><br />
                                <br />
                                @if (Lang::locale() == 'lt')
                                    Nariai:<br />
                                @else
                                    Members:<br />
                                @endif
                                <?php $members = explode(',', $parlDB->members); ?>
                                @for ($i = 0; $i < sizeof($members); $i++)
                                    {{ $members[$i] }}<br />
                                @endfor
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endsection
