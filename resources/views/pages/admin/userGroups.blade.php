@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Administratoriai
                <small>Pridėti, šalinti, redaguoti</small>
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/vartotojai' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li class="active">Vartotojai</li>': '' !!}
            </ol>
        </section>
        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <a class="btn btn-primary" href="/admin/vartotojai/prideti">Pridėti vartotoją</a>
                    <br/>
                    <br/>

                    <div>
                        @if (Session::has('message'))
                            <div class="alert alert-success" role="alert">{{ Session::get('message') }}</div>
                        @endif
                    </div>
                    <table class="table table-bordered">
                        <tr class="alert alert-success">
                            <th>Vardas, Pavardė [vartotojo vardas]</th>
                            <th>Tipas</th>
                            <th>Veiksmai</th>
                        </tr>
                        @foreach ($users as $user)
                            <tr>
                                <td><b>{{ $user['realname'] . ' [' . $user['username'] . ']' }}</b></td>
                                <td>
                                    <?php
                                    switch ($user['gid']) {
                                        case 1:
                                            echo "Administratoriai";
                                            break;
                                        case 2:
                                            echo "Pildo savo darbotvarkes";
                                            break;
                                        case 3:
                                            echo "Pildo visų darbotvarkes ir kalendorių";
                                            break;
                                        case 4:
                                            echo "VU SA IF";
                                            break;
                                        case 5:
                                            echo "VU SA FF";
                                            break;
                                    } ?>
                                </td>
                                <td>
                                    <a id="{{$user['id']}}" class="deleteRow" alt="Šalinti">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    &nbsp;
                                    <a href="/admin/vartotojai/{{$user['username']}}/redaguoti" alt="Redaguoti">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    &nbsp;
                                    <a href="/admin/vartotojai/{{$user['username']}}/keistislaptazodi" alt="Keisti slaptažodį">
                                        <span class="fas fa-key"></span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                    {!! $users->appends(['sort' => 'username'])->links() !!}
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
                    $.get('/admin/vartotojai/deleteRow?itemId=' + id, function(data) {});
                    row.parent().parent().remove();
                    swal.fire("Ištrinta!", "Pasirinktas vartotojas ištrintas.", "success");
                })()
            }
        })
    })
    </script>
@endsection