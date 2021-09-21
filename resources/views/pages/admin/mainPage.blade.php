@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>Pradinio puslapio elementų valdymas</h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/pagrindinis' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li class="active">Pradinio puslapio elementai</li>': '' !!}
            </ol>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <div>
                        @if (Session::has('message'))
                            <div class="alert alert-info" role="alert">{{ Session::get('message') }}</div>
                        @endif
                    </div>
                    @if($sessionInfo->gid == 1)
                        <div class="row">
                            @for($i = 1; $i < 10; $i++)
                                @if($i % 3 == 0 )
                                    <div class="col-md-4" style="text-align: center">
                                        <a href="/admin/pagrindinis/{{$i}}"><h2>{{$i}} kvadratas</h2></a>
                                    </div>
                        </div>
                        <div class="row">
                            @else
                                <div class="col-md-4" style="text-align: center">
                                    <a href="/admin/pagrindinis/{{$i}}"><h2>{{$i}} kvadratas</h2></a>
                                </div>
                            @endif
                            @endfor
                        </div>
                    @else
                        <h4>Šoninio meniu elementai</h4>

                        <p>Jeigu šoniniame meniu nėra pridėta koordinatorių ar kuratorių nuoroda, pvz.: /lt/kontaktai/koordinatoriai - ji atsiras automatiškai. ;)</p>
                        
                        <div class="col-md-7">
                            <a class="btn btn-success" href="/admin/pagrindinis/{{$userGroupAlias}}/prideti?position=sideItem">Pridėti šoninį elementą</a>
                        </div>
                        <br/><br/>

                        <table class="table">
                            <tr class="alert alert-warning">
                                <th>Pavadinimas</th>
                                <th>Adresas</th>
                                <th>Tipas</th>
                                <th colspan="3">Veiksmai</th>
                            </tr>
                            @foreach($sideMenus as $sideMenu)
                                <tr class="alert alert-success">
                                    <td>{{$sideMenu['text']}}</td>
                                    <td>{{$sideMenu['link']}}</td>
                                    <td>{{$sideMenu['type']}}</td>
                                    <td>
                                        <a style="text-decoration:none" href="/admin/pagrindinis/{{$userGroupAlias}}/{{$sideMenu['id']}}/redaguoti?position=sideItem">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        &nbsp;
                                        <a id="{{$sideMenu['id']}}" class=" deleteRow" aria-hidden="true">
                                            <i
                                            class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                    <td>
                                        @if ($sideMenu->orderID != 1)
                                            <a href="/admin/pagrindinis/swap/{{$sideMenu['id']}}/up?position=sideItem">
                                                <i class="fas fa-chevron-up"></i>
                                            </a>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($sideMenu->orderID != $sideMenuSize)
                                            <a href="/admin/pagrindinis/swap/{{$sideMenu['id']}}/down?position=sideItem">
                                                <i class="fas fa-chevron-down"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>

                        <h4>Apatinio meniu elementai</h4>

                        <div class="col-md-7">
                            <a class="btn btn-success" href="/admin/pagrindinis/{{$userGroupAlias}}/prideti?position=bottomItem">Pridėti apatinį elementą</a>
                        </div>
                        <br/><br/>

                        <table class="table">
                            <tr class="alert alert-warning">
                                <th>Pavadinimas</th>
                                <th>Adresas</th>
                                <th>Tipas</th>
                                <th colspan="3">Veiksmai</th>
                            </tr>
                            @foreach($bottomMenus as $bottomMenu)
                                <tr class="alert alert-success">
                                    <td>{{$bottomMenu['text']}}</td>
                                    <td>{{$bottomMenu['link']}}</td>
                                    <td>{{$bottomMenu['type']}}</td>
                                    <td>
                                        <a style="text-decoration:none" href="/admin/pagrindinis/{{$userGroupAlias}}/{{$bottomMenu['id']}}/redaguoti?position=bottomItem">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        &nbsp;
                                        <a id="{{$bottomMenu['id']}}" class=" deleteRow" aria-hidden="true">
                                            <i
                                            class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                    <td>
                                        @if ($bottomMenu->orderID != 1)
                                            <a href="/admin/pagrindinis/swap/{{$bottomMenu['id']}}/up?position=bottomItem">
                                                <i class="fas fa-chevron-up"></i>
                                            </a>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($bottomMenu->orderID != $bottomMenuSize)
                                            <a href="/admin/pagrindinis/swap/{{$bottomMenu['id']}}/down?position=bottomItem">
                                                <i class="fas fa-chevron-down"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>

                        <h4>Aprašymas</h4>

                        <div class="col-md-7">
                            <a class="btn btn-success" href="/admin/pagrindinis/{{$userGroupAlias}}/{!! rand (100,60) !!}/redaguoti?position=lowbottom">Redaguoti aprašymą</a>
                        </div>
                        <br/><br/><br/>
                        @if(!empty($description[0]->text))
                            <div>{!! $description[0]->text !!}</div>
                        @endif
                    @endif
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
                    $.get('/admin/pagrindinis/delete?itemId=' + id, function(data) {});
                    row.parent().parent().remove();
                    swal.fire("Ištrinta!", "Pasirinktas pagrindinio puslapio elementas ištrintas.", "success");
                })()
            }
        })
    })
    </script>
@endsection