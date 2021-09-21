@extends('layouts.user.master')

@section('title'){{'Užregistruoti egzaminai/koliokviumai stebėjimui'}}@endsection

@section('meta')
    <meta property="og:url" content="{{"http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']}}"/>
    <meta property="og:type" content="website"/>
    <meta property="og:title" content="VU SA | Užregistruoti egzaminai/koliokviumai stebėjimui"/>
    <meta property="og:description" content=""/>
    <meta property="og:image" content="/images/icons/logos/vusa.lin.hor.png"/>
@endsection

@section('content')
    <div class="container" id="infoPage">
        <div class="pageTitle">Užregistruoti atsiskaitymai stebėjimui</div>
        <div class="col-lg-12 infoPage" id="infoPageText">
            <table class="table table-bordered">
                <tr>
                    <th>Laikomo dalyko pavadinimas</th>
                    <th>Laikančiųjų padalinys</th>
                    <th>Laikančiųjų skaičius</th>
                    <th>Atsiskaitymo data ir laikas</th>
                    <th>Atsiskaitymo vieta</th>
                    <th>Atsiskaitymo trukmė</th>
                    <th>Reikalingas stebėtojų skaičius</th>
                    <th>Užsiregistravusių stebėtojų skaičius</th>
                    <th></th>
                </tr>
                @foreach($atsiskaitymai as $atsiskaitymas)
                    <tr>
                        <td>{{$atsiskaitymas['subject_name']}}</td>
                        <td>
                            <?php
                            switch ($atsiskaitymas['padalinys']) {
                                case 'chgf':
                                    $name = 'CHGF';
                                    break;
                                case 'evaf':
                                    $name = 'EVAF';
                                    break;
                                case 'ff':
                                    $name = 'FF';
                                    break;
                                case 'filf':
                                    $name = 'FiLF';
                                    break;
                                case 'fsf':
                                    $name = 'FsF';
                                    break;
                                case 'gmc':
                                    $name = 'GMC';
                                    break;
                                case 'if':
                                    $name = 'IF';
                                    break;
                                case 'kf':
                                    $name = 'KF';
                                    break;
                                case 'knf':
                                    $name = 'KnF';
                                    break;
                                case 'mf':
                                    $name = 'MF';
                                    break;
                                case 'mif':
                                    $name = 'MIF';
                                    break;
                                case 'tf':
                                    $name = 'TF';
                                    break;
                                case 'tspmi':
                                    $name = 'TSPMI';
                                    break;
                                case 'vm':
                                    $name = 'VM';
                                    break;
                            }
                            ?>
                            {{$name}}
                        </td>
                        <td>{{$atsiskaitymas['count']}}</td>
                        <td>
                            <?php $timeArr = explode('|', $atsiskaitymas->time); ?>
                            @foreach($timeArr as $time)
                                @if(strlen($time) > 2)
                                    {{$time}}<br/>
                                @endif
                            @endforeach
                        </td>
                        <td>{{$atsiskaitymas['place']}}</td>
                        <td>{{$atsiskaitymas['duration']}}</td>
                        <td>
                            @if($atsiskaitymas['students_need'] == 0)
                                Nėra informacijos
                            @else
                                {{$atsiskaitymas['students_need']}}
                            @endif
                        </td>
                        <td>
                            @for($count = 1; $count <= sizeof($atsiskaitymas['students_registered']); $count++)
                                {{$atsiskaitymas['students_registered'][$count]}}<br/>
                            @endfor
                        </td>
                        <td><a href="/lt/registracija-stebejimui?uuid={{$atsiskaitymas['uuid']}}">Registruotis</a></td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
@endsection