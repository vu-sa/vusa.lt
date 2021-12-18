@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Darbotvarkė
            </h1>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <a class="btn btn-success" href="/admin/agenda/create">Pridėti darbotvarkės įrašą</a>
                    <br/>
                    <br/>

                    <table class="table">
                        <tr class="alert alert-warning">
                            <th>Pavadinimas</th>
                            <th>Data</th>
                            <th>Veiksmai</th>
                        </tr>
                        @foreach ($agenda as $agendaEvent)
                            <tr class="alert alert-success">
                                <td>{{ $agendaEvent['title']}}</td>
                                <td>{{ $agendaEvent['date']}}</td>
                                <td>
                                    <a style="text-decoration:none" href="/admin/agenda/{{$agendaEvent['id']}}/edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    &nbsp;
                                    <a id="{{$agendaEvent['id']}}" class="deleteRow" aria-hidden="true">
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
        $('.deleteRow').on('click', function (e) {
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
                        axios.post("{{ route('pages.admin.agenda.destroy', '', false)}}", {
                            id: id
                        });
                        row.parent().parent().remove();
                        swal.fire("Ištrinta!", "Pasirinktas darbotvarkės punktas ištrintas.", "success");
                    })()
                }
            })
        });
    </script>
@endsection