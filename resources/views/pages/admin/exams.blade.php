@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Sąžiningai
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/saziningai' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li class="active">Sąžiningai</li>': '' !!}
            </ol>
        </section>
        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <div class="col-md-offset-7 col-md-5">
                        {{ Form::open(['method' => 'GET'])}}
                        <div class="input-group">
                            {{ Form::text('searchText', $searchText, array('class'=>'form-control', 'placeholder'=>'Paieška pagal pavadinimą')) }}

                            <span class="input-group-btn">
                            {{Form::submit('Ieškoti',['class'=>'btn btn-default'])}}
                            </span>
                            {{ Form::close() }}
                        </div>
                    </div>
                    <br/>
                    <br/>
                    <br/>

                    <div>
                        @if (Session::has('message'))
                            <div class="alert alert-success" role="alert">{{ Session::get('message') }}</div>
                        @endif
                    </div>
                    <table class="table table-bordered">
                        <tr class="alert alert-success">
                            <th>Laikomo dalyko pavadinimas</th>
                            <th>Laikančiųjų padalinys</th>
                            <th>Laikančiųjų skaičius</th>
                            <th>Atsiskaitymo data ir laikas</th>
                            <th>Atsiskaitymo vieta</th>
                            <th>Atsiskaitymo trukmė</th>
                            <th>Reikia stebėtojų</th>
                            <th>Užsiregistravę stebėtojai</th>
                            <th>Užregistravimo data</th>
                            <th></th>
                        </tr>
                        @foreach($atsiskaitymai as $atsiskaitymas)
                            <tr>
                                <td>{{$atsiskaitymas->subject_name}}</td>
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
                                        case    'kf':
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
                                        case 'sa':
                                            $name = "ŠA";
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
                                <td>{{$atsiskaitymas->count}}</td>
                                <td>
                                    <?php
                                    $timeArr = explode('|', $atsiskaitymas->time);
                                    ?>
                                    @foreach($timeArr as $time)
                                        @if(strlen($time) > 2)
                                            {{$time}}<br/>
                                        @endif
                                    @endforeach
                                </td>
                                <td>{{$atsiskaitymas->place}}</td>
                                <td>{{$atsiskaitymas->duration}}</td>
                                <td>{{$atsiskaitymas->students_need}}</td>
                                <td>{{$atsiskaitymas->students_registered}}</td>
                                <td>{{$atsiskaitymas->registration_time}}</td>
                                <td>
                                    <a href="/admin/saziningai/{{$atsiskaitymas->uuid}}/redaguoti?page={{$_GET["page"]}}">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    &nbsp;
                                    <a id="{{$atsiskaitymas->uuid}}" class="deleteRow" aria-hidden="true">
                                        <i
                                        class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                    {!! $atsiskaitymai->appends(['sort' => 'id'])->links() !!}
                </div>
            </div>
        </section>
    </div>

    <script>
        $('.deleteRow').on('click', function(e) {

        var uuid = $(this).attr('id');
        var row = $(this);

        swal.fire({
            title: "Ar tikrai nori pašalinti?",
            text: "Ištrinto elemento atkurti nebebus galima!",
            icon: "warning",
            showCancelButton: true,
            cancelButtonText: "Ne, palikti!",
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Taip, šalinti!",
            closeOnConfirm: false
        }).then((result) => {
            if (result.isConfirmed) {
                (function () {
                    $.get('/admin/saziningai/delete?uuid=' + uuid, function(data) {});
                    row.parent().parent().remove();
                    swal.fire("Ištrinta!", "Stebėjimas ištrintas.", "success");
                })()
            }
        })
    })
    </script>
@endsection