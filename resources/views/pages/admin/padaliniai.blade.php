@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Padaliniai
                <small>Pridėti, šalinti, redaguoti</small>
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/padaliniai' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li class="active">Padaliniai</li>': '' !!}
            </ol>
        </section>
        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <a class="btn btn-primary" href="/admin/padaliniai/prideti">Pridėti padalinį</a>
                    <br/>
                    <br/>

                    <div>
                        @if (Session::has('message'))
                            <div class="alert alert-success" role="alert">{{ Session::get('message') }}</div>
                        @endif
                    </div>
                    <table class="table table-bordered">
                        <tr class="alert alert-success">
                            <th>Pavadinimas</th>
                            <th>Veiksmai</th>
                        </tr>
                        @foreach ($padaliniai as $padalinys)
                            <tr>
                                <td><b>{{ $padalinys->shortname }}</b></td>
                                <td>
                                    {{--<span id="{{$padalinys['id']}}" class="glyphicon glyphicon-trash deleteRow" alt="Šalinti"></span>--}}
                                    {{--&nbsp;--}}
                                    <a href="/admin/padaliniai/{{$padalinys->alias}}/redaguoti" alt="Redaguoti">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </table>
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
                    $.get('/admin/padaliniai/deleteRow?itemId=' + id, function(data) {});
                    row.parent().parent().remove();
                    swal.fire("Ištrinta!", "Pasirinktas padalinys ištrintas.", "success");
                })()
            }
        })
    })
    </script>
@endsection