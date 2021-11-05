@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Kontaktai
                <small>{{$name}}</small>
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute.'/'.$name == 'admin/kontaktai/{name}/'.$name ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li>Kontaktai </li> <li class="active">'.$name.'</li>': '' !!}
            </ol>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <a class="btn btn-primary" href="/admin/kontaktai/prideti?group={{$name2}}">Pridėti kontaktą</a>
                    <br/>
                    <br/>

                    <div>
                        @if (Session::has('message'))
                            <div class="alert alert-success" role="alert">{{ Session::get('message') }}</div>
                        @endif
                    </div>
                    @if ($name == 'Centrinis biuras' || $name == 'Koordinatoriai' || $name == 'Padalinio taryba' || $name == 'Studentu atstovai' || $name == 'Kuratoriai')
                        <table class="table">
                            <tr class="alert alert-success">
                                <th>Vardas, pavardė</th>
                                <th>Pareigos</th>
                                <th>El. paštas</th>
                                <th>Telefonas</th>
                                <th colspan="4">Veiksmas</th>
                            </tr>
                            @foreach($contacts as $contact)
                                <tr>
                                    <td>{{$contact['name']}}</td>
                                    <td>{{$contact['duties']}}</td>
                                    <td>{{$contact['email']}}</td>
                                    <td>{{$contact['phone']}}</td>
                                    <td colspan="2">
                                        <a style="text-decoration:none" id="{{$contact['id']}}" class="deleteRow" alt="Šalinti">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        &nbsp;
                                        <a style="text-decoration:none" href="/admin/kontaktai/{{$contact['id']}}/redaguoti" alt="Redaguoti">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                    <td>
                                        @if ($contacts->min('contactOrder') != $contact['contactOrder'])
                                            <a href="/admin/kontaktai/swap/{{$contact['id']}}/up?category={{$contact['groupname']}}">
                                                <i class="fas fa-chevron-up"></i>
                                            </a>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($contacts->max('contactOrder') != $contact['contactOrder'])
                                            <a href="/admin/kontaktai/swap/{{$contact['id']}}/down?category={{$contact['groupname']}}">
                                                <i class="fas fa-chevron-down"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                    @if ($name == 'Centrinis biuras EN')
                        <table class="table">
                            <tr class="alert alert-success">
                                <th>Vardas, pavardė</th>
                                <th>Pareigos</th>
                                <th>El. paštas</th>
                                <th>Telefonas</th>
                                <th colspan="4">Veiksmas</th>
                            </tr>
                            @foreach($contacts as $contact)
                                <tr>
                                    <td>{{$contact['name']}}</td>
                                    <td>{{$contact['duties']}}</td>
                                    <td>{{$contact['email']}}</td>
                                    <td>{{$contact['phone']}}</td>
                                    <td colspan="2">
                                        <a style="text-decoration:none" id="{{$contact['id']}}" class="deleteRow" alt="Šalinti">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        &nbsp;
                                        <a href="/admin/kontaktai/{{$contact['id']}}/redaguoti" alt="Redaguoti">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                    <td>
                                        @if ($contact['contactOrder'] != 1)
                                            <a href="/admin/kontaktai/swap/{{$contact['id']}}/up?category={{$contact['groupname']}}">
                                                <i class="fas fa-chevron-up"></i>
                                            </a>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($contact['contactOrder'] != sizeof($contacts))
                                            <a href="/admin/kontaktai/swap/{{$contact['id']}}/down?category={{$contact['groupname']}}">
                                                <i class="fas fa-chevron-down"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                    @if ($name == 'Padaliniai')
                        <table class="table">
                            <tr class="alert alert-success">
                                <th>Pavadinimas</th>
                                <th>Veiksmas</th>
                            </tr>
                            @foreach($contacts as $contact)
                                <tr>
                                    <td>{{$contact['name_full']}}</td>
                                    <td>
                                        <a style="text-decoration:none" id="{{$contact['id']}}" class="deleteRow" alt="Šalinti">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        &nbsp;
                                        <a href="/admin/kontaktai/{{$contact['id']}}/redaguoti" alt="Redaguoti">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                    @if ($name == 'Taryba')
                        <table class="table">
                            <tr class="alert alert-success">
                                <th>Vardas, pavardė</th>
                                <th>Padalinys</th>
                                <th>Veiksmas</th>
                            </tr>
                            @foreach($contacts as $contact)
                                <tr>
                                    <td>{{$contact['name']}}</td>
                                    <td>{{$contact['name_short']}}</td>
                                    <td>
                                        <a style="text-decoration:none" id="{{$contact['id']}}" class="deleteRow" alt="Šalinti">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        &nbsp;
                                        <a href="/admin/kontaktai/{{$contact['id']}}/redaguoti" alt="Redaguoti">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                    @if ($name == 'Parlamentas')
                        <table class="table">
                            <tr class="alert alert-success">
                                <th>Padalinys/Pareigos</th>
                                <th>Vardas, pavardė/Nariai</th>
                                <th colspan="3">Veiksmas</th>
                            </tr>
                            @foreach($contacts as $contact)
                                <tr>
                                    <td>
                                        @if($contact['name_short'] == '')
                                            Parlamento pirmininkas(-ė)
                                        @else
                                            {{$contact['name_short']}}
                                        @endif
                                    </td>
                                    <td>
                                        @if($contact['name'] == '')
                                            {{$contact['members']}}
                                        @else
                                            {{$contact['name']}}
                                        @endif
                                    </td>
                                    <td>
                                        @if($contact->groupname != 'parl-pirm')
                                            @if ($contact['contactOrder'] != 0)
                                                <a href="/admin/kontaktai/swap/{{$contact['id']}}/up?category={{$contact['groupname']}}">
                                                    <i class="fas fa-chevron-up"></i>
                                                </a>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if($contact->groupname != 'parl-pirm')
                                            @if ($contact['contactOrder'] != sizeof($contacts)-2)
                                                <a href="/admin/kontaktai/swap/{{$contact['id']}}/down?category={{$contact['groupname']}}">
                                                    <i class="fas fa-chevron-down"></i>
                                                </a>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if($contact->groupname != 'parl-pirm')
                                            <a style="text-decoration:none" id="{{$contact['id']}}" class="deleteRow" alt="Šalinti">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                            &nbsp;
                                        @endif
                                        <a href="/admin/kontaktai/{{$contact['id']}}/redaguoti" alt="Redaguoti">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                    @if ($name == 'Parlamento darbo grupės')
                        <table class="table">
                            <tr class="alert alert-success">
                                <th>Pavadinimas</th>
                                <th>Pirmininko vardas, pavardė</th>
                                <th>Nariai</th>
                                <th colspan="3">Veiksmas</th>
                            </tr>
                            @foreach($contacts as $contact)
                                <tr>
                                    <td>
                                        {{$contact['name_full']}}
                                    </td>
                                    <td>
                                        {{$contact['name']}}
                                    </td>
                                    <td>
                                        {{$contact['members']}}
                                    </td>
                                    <td>
                                        <a style="text-decoration:none" id="{{$contact['id']}}" class="deleteRow" alt="Šalinti">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        &nbsp;
                                        <a href="/admin/kontaktai/{{$contact['id']}}/redaguoti" alt="Redaguoti">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                    @if ($name == 'Revizija')
                        <table class="table">
                            <tr class="alert alert-success">
                                <th>Pirmininkė/as</th>
                                <th>Padaliniai</th>
                                <th>Veiksmai</th>
                            </tr>
                            @foreach($contacts as $contact)
                                <tr>
                                    <td>
                                        {{$contact['name']}}
                                    </td>
                                    <td>
                                        {{$contact['members']}}
                                    </td>
                                    <td>
                                        <a style="text-decoration:none" id="{{$contact['id']}}" class="deleteRow" alt="Šalinti">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        &nbsp;
                                        <a href="/admin/kontaktai/{{$contact['id']}}/redaguoti" alt="Redaguoti">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                    @if ($name == 'Institucinio stiprinimo fondas')
                        <table class="table">
                            <tr class="alert alert-success">
                                <th>Vardas, pavardė</th>
                                <th>Padaliniai</th>
                                <th>Veiksmai</th>
                            </tr>
                            @foreach($contacts as $contact)
                                <tr>
                                    <td>
                                        {{$contact['name']}}
                                    </td>
                                    <td>
                                        {{$contact['members']}}
                                    </td>
                                    <td>
                                        <a style="text-decoration:none" id="{{$contact['id']}}" class="deleteRow" alt="Šalinti">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        &nbsp;
                                        <a href="/admin/kontaktai/{{$contact['id']}}/redaguoti" alt="Redaguoti">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                    @if ($name == 'Studentų atstovai LT')
                        <table class="table">
                            <tr class="alert alert-success">
                                <th>Vardas, pavardė</th>
                                <th>Grupė</th>
                                <th>Veiksmai</th>
                            </tr>
                            @foreach($contacts as $contact)
                                <tr>
                                    <td>
                                        {{$contact['name']}}
                                    </td>
                                    <td>
                                        {{$contact['grouptitle']}}
                                    </td>
                                    <td>
                                        <a style="text-decoration:none" id="{{$contact['id']}}" class="deleteRow" alt="Šalinti">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        &nbsp;
                                        <a href="/admin/kontaktai/{{$contact['id']}}/redaguoti" alt="Redaguoti">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                    @if ($name == 'Studentų atstovai EN')
                        <table class="table">
                            <tr class="alert alert-success">
                                <th>Vardas, pavardė</th>
                                <th>Grupė</th>
                                <th>Veiksmai</th>
                            </tr>
                            @foreach($contacts as $contact)
                                <tr>
                                    <td>
                                        {{$contact['name']}}
                                    </td>
                                    <td>
                                        {{$contact['grouptitle']}}
                                    </td>
                                    <td>
                                        <a style="text-decoration:none" id="{{$contact['id']}}" class="deleteRow" alt="Šalinti">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        &nbsp;
                                        <a href="/admin/kontaktai/{{$contact['id']}}/redaguoti" alt="Redaguoti">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                    @if ($name == 'Programos, klubai ir projektai')
                        <table class="table">
                            <tr class="alert alert-success">
                                <th>Programa</th>
                                <th>Vardas pavardė</th>
                                <th>Veiksmai</th>
                            </tr>
                            @foreach($contacts as $contact)
                                <tr>
                                    <td>
                                        {{$contact['name_full']}}
                                    </td>
                                    <td>
                                        {{$contact['name']}}
                                    </td>
                                    <td>
                                        <a style="text-decoration:none" id="{{$contact['id']}}" class="deleteRow" alt="Šalinti">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        &nbsp;
                                        <a href="/admin/kontaktai/{{$contact['id']}}/redaguoti" alt="Redaguoti">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                    @if ($name == 'aprasymas' || $name == 'aprasymas-padalinys')
                        <table class="table">
                            <tr class="alert alert-success">
                                <th>Grupė</th>
                                <th>Informacinis tekstas</th>
                                <th>Veiksmai</th>
                            </tr>
                            @foreach($contacts as $contact)
                                <tr>
                                    <td>
                                        {{$contact['grouptitle']}}
                                    </td>
                                    <td>
                                        {!! $contact['infoText'] !!}
                                    </td>
                                    <td>
                                        <a style="text-decoration:none" id="{{$contact['id']}}" class="deleteRow" alt="Šalinti">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        &nbsp;
                                        <a href="/admin/kontaktai/{{$contact['id']}}/redaguoti" alt="Redaguoti">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                </div>
            </div>
        </section>
    </div>
    <script>
        $('.deleteRow').on('click', function(e) {

        var id = $(this).attr('id');
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
                    $.get('/admin/kontaktai/deleteRow?itemId=' + id, function(data) {});
                    row.parent().parent().remove();
                    swal.fire("Ištrinta!", "Pasirinktas kontaktas ištrintas.", "success");
                })()
            }
        })
    })
    </script>
@endsection