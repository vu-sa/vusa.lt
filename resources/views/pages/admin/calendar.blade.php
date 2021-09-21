@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Kalendorius
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/kalendorius' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li class="active">Kalendorius ir darbotvarkė</li>': '' !!}
            </ol>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <a class="btn btn-success" href="/admin/kalendorius/prideti">Pridėti kalendoriaus įrašą</a>
                    <br/>
                    <br/>

                    <div>
                        @if (Session::has('message'))
                            <div class="alert alert-success-member" role="alert">{{ Session::get('message') }}</div>
                        @endif
                    </div>

                    @if (count($errors) > 0)
                        <div class="alert alert-danger" role="alert">
                            <ul>
                                <?php $errors = array_unique($errors->all());?>
                                @foreach ($errors as $error)
                                    @if($error == 'validation.required')
                                        <li>Puslapio pavadinimo, kategorijos bei puslapio teksto laukai turi būti užpildyti.</li>
                                    @elseif($error == 'validation.unique')
                                        <li>Puslapis su tokiu pavadinimu jau egzistuoja.</li>
                                    @else
                                        <li>{{$error}}</li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <table class="table">
                        <tr class="alert alert-warning">
                            <th>Pavadinimas</th>
                            <th>Data</th>
                            <th>Veiksmai</th>
                        </tr>
                        @foreach ($events as $event)
                            <tr class="alert alert-success">
                                <td>{{ $event['title']}}</td>
                                <td>{{ $event['date'].' '.$event['time']}}</td>
                                <td>
                                    <a style="text-decoration:none" href="/admin/kalendorius/{{$event['id']}}/redaguoti">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    &nbsp;
                                    <a id="{{$event['id']}}" 
                                    class="deleteRowCal" 
                                    aria-hidden="true">
                                    <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                    {!! $events !!}
                </div>
            </div>
        </section>
    </div>
    <script>
        $('.deleteRowCal').on('click', function(e) {

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
                    $.get('/admin/kalendorius/deleteRowCalendar?itemId=' + id, function(data) {});
                    row.parent().parent().remove();
                    swal.fire("Ištrinta!", "Pasirinktas kalendoriaus įvykis ištrintas.", "success");
                })()
            }
        })
    })
    </script>
@endsection