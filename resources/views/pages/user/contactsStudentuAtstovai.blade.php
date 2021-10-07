@extends('layouts.user.master')

@if (Lang::locale() == 'lt')
    @section('title', 'Studentų atstovų kontaktai')
    @else
    @section('title', 'Studentų atstovų Contacts')
    @endif

    @section('content')
        <div class="container">
            @if (Lang::locale() == 'lt')
                <div class="pageTitle">Studentų atstovai</div>
            @else
                <div class="pageTitle">Students contacts</div>
            @endif

            @if ($contactGroupDescription ?? '')
                <div style="padding-bottom: 20px;">
                    {!! $contactGroupDescription !!}
                </div>
            @endif

            <div class="contactRowTaryba">
                @foreach ($contactsGroups as $contactsGroup)
                    <h3>{{ $contactsGroup->grouptitle }}</h3>
                    <?php
                    $index = 0;
                    $memberCount = 0;
                    ?>
                    @foreach ($contacts as $contact)
                        @if ($contact->grouptitle == $contactsGroup->grouptitle)
                            <?php $memberCount += 1; ?>
                        @endif
                    @endforeach
                    <div class="row">
                    @foreach ($contacts as $contact)
                        @if ($contact->grouptitle == $contactsGroup->grouptitle)
                            <?php $index += 1; ?>
                            <div class="col-md-4 tarybaItem">
                                <div class="tarybaName">
                                    <br />
                                    {{ $contact->name }}
                                </div>
                                <div class="tarybaLine" style="background-color: #bd2835"></div>

                                <div class="tarybaNameFull">
                                    @if (Lang::locale() == 'lt')
                                        Tel.
                                    @else
                                        Phone
                                    @endif
                                    {{ $contact->phone }}<br />
                                    <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a><br />
                                </div>
                            </div>
                            {!! $index % 3 === 0 || $index == $memberCount
                            ? '
            </div>
            <div class="row contactRowTaryba"> '
                : '' !!}
                @endif
                @endforeach
            </div>
                @endforeach
            </div>
        </div>
        <br>
@endsection
