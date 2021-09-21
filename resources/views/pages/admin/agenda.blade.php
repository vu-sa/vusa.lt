@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Darbotvarkė
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/kalendorius' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li class="active">Kalendorius ir darbotvarkė</li>': '' !!}
            </ol>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <a class="btn btn-success" href="/admin/darbotvarke/prideti">Pridėti darbotvarkės įrašą</a>
                    <br/>
                    <br/>

                    <table class="table">
                        <tr class="alert alert-warning">
                            <th>Pavadinimas</th>
                            <th>Data</th>
                            <th>Veiksmai</th>
                        </tr>
                        @foreach ($agenda as $event2)
                            <tr class="alert alert-success">
                                <td>{{ $event2['title']}}</td>
                                <td>{{ $event2['date']}}</td>
                                <td>
                                    <a style="text-decoration:none" href="/admin/darbotvarke/{{$event2['id']}}/redaguoti">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    &nbsp;
                                    <a id="{{$event2['id']}}" class="deleteRowAgenda" aria-hidden="true">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                    {!! $agenda !!}
                </div>
            </div>
        </section>
    </div>
    <script>
        $('.deleteRowAgenda').on('click', function (e) {
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
                        $.get('/admin/darbotvarke/deleteRowAgenda?itemId=' + id, function(data) {});
                        row.parent().parent().remove();
                        swal.fire("Ištrinta!", "Pasirinktas darbotvarkės punktas ištrintas.", "success");
                    })()
                }
            })
        });
    </script>
@endsection