@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Reklama
                <small>Rėmėjų baneriai</small>
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/reklama' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li class="active">Reklamos baneriai</li>': '' !!}
            </ol>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <a class="btn btn-success" href="/admin/reklama/prideti">Pridėti reklaminį banerį</a>
                    <br/>
                    <br/>

                    <div>
                        @if (Session::has('message'))
                            <div class="alert alert-info" role="alert">{{ Session::get('message') }}</div>
                        @endif
                    </div>

                    <table class="table">
                        <tr class="alert alert-warning">
                            <th>Pavadinimas</th>
                            <th>Nuoroda</th>
                            <th>Kūrėjo grupė</th>
                            <th>Nuotrauka</th>
                            <th>Veiksmai</th>
                        </tr>
                        @foreach ($banners as $banner)
                            <tr class="alert alert-success">
                                <td>{{ $banner['title']}}</td>
                                <td><a target="_blank" rel="noopener" href="/lt/{{ $banner['url']}}">{{ $banner['url']}}</a></td>
                                <td>{{ $banner['descr'] }}</td>
                                <td class="hover_img">
                                    <a>Nuotrauka
                                        <?php
                                        if (strpos($banner['value'], 'vusa.lt') !== false) {
                                            $imageLocation = $banner['value'];
                                        } else {
                                            $imageLocation = '/uploads/sidebar/' . $banner['value'];
                                        }
                                        ?>
                                        <span> <img style="margin-left: 70px; margin-top: -20px;" src="{{$imageLocation }}" height="100"
                                                    alt="nera"/> </span>
                                    </a>
                                </td>
                                <td>
                                    @if ($banner['hide'] == 0)
                                    <a style="text-decoration:none" id="{{ $banner['id'] }}" class="changeView"
                                        aria-hidden="true">
                                    <i class="fas fa-eye"></i>
                                    </a>
                                    @else
                                    <a style="text-decoration:none" id="{{ $banner['id'] }}" class="changeView"
                                    aria-hidden="true">
                                    <i class="fas fa-eye-slash"></i>
                                    </a>
                                    @endif
                                    &nbsp;
                                    <a style="text-decoration:none" href="/admin/reklama/{{$banner['id']}}/redaguoti">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    &nbsp;
                                    <a id="{{$banner['id']}}" class="deleteRow" aria-hidden="true">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                    {!! $banners !!}
                </div>
            </div>
        </section>
    </div>

    <script>
        $('.changeView').on('click', function (e) {
            var id = $(this).attr('id');

            if ($(this).children().hasClass('fa-eye-slash')) {
                $(this).children().removeClass('fa-eye-slash');
                $(this).children().addClass('fa-eye');
                $.get('/admin/reklama/changeView?itemId=' + id, function (data) {
                });
            }
            else {
                $(this).children().removeClass('fa-eye');
                $(this).children().addClass('fa-eye-slash');
                $.get('/admin/reklama/changeView?itemId=' + id, function (data) {
                });
            }
        });

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
                        $.get('/admin/reklama/deleteRow?itemId=' + id, function(data) {});
                        row.parent().parent().remove();
                        swal.fire("Ištrinta!", "Pasirinktas baneris ištrintas.", "success");
                    })()
                }
            })
        })
    </script>
@endsection