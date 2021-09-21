@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Grupės
                <small>Pridėti, šalinti, redaguoti</small>
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/grupes' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li class="active">Grupės</li>': '' !!}
            </ol>
        </section>
        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <a class="btn btn-primary" href="/admin/grupes/prideti">Pridėti grupę</a>
                    <br/>
                    <br/>

                    <div>
                        @if (Session::has('message'))
                            <div class="alert alert-success" role="alert">{{ Session::get('message') }}</div>
                        @endif
                    </div>
                    <table class="table table-bordered">
                        <tr class="alert alert-success">
                            <th>ID</th>
                            <th>Grupės pavadinimas</th>
                            <th>Veiksmai</th>
                        </tr>
                        @foreach ($groups as $group)
                            <tr>
                                <td><b>{{ $group['id'] }}</b></td>
                                <td><b>{{ $group['descr'] }}</b></td>
                                <td>
                                    <a id="{{$group['id']}}" class="deleteRow" alt="Šalinti">
                                        <i
                                        class="fas fa-trash"></i>
                                    </a>
                                    &nbsp;
                                    <a href="/admin/grupes/{{$group['id']}}/redaguoti" alt="Redaguoti">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                    {!! $groups->appends(['sort' => 'descr'])->links() !!}
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
                    $.get('/admin/grupes/deleteRow?itemId=' + id, function(data) {});
                    row.parent().parent().remove();
                    swal.fire("Ištrinta!", "Pasirinkta grupė ištrinta.", "success");
                })()
            }
        })
    })
    </script>
@endsection