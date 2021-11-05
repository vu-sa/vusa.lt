@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                {!! $currentRoute == 'admin/navigacijaLT' ? 'Navigacija LT': '' !!}
                {!! $currentRoute == 'admin/navigacijaEN' ? 'Navigacija EN': '' !!}
                <small>Tinklapio meniu</small>
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/navigacijaLT' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li class="active">Navigacija LT</li>': '' !!}
                {!! $currentRoute == 'admin/navigacijaEN' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li class="active">Navigacija EN</li>': '' !!}
            </ol>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <a class="btn btn-success" href="/admin/navigacija/prideti">Pridėti navigacijos elementą</a>
                    <br/>
                    <br/>

                    <div class="table-responsive">
                        <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                            <div class="panel panel-default">
                                <table class="table">
                                    <tr>
                                        <th colspan="2">Pavadinimas</th>
                                        <th></th>
                                        <th></th>
                                        <th>Nuoroda</th>
                                        <th>Veiksmai</th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                    {!! $navigacija !!}
                                </table>
                            </div>
                        </div>
                    </div>
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
                $.get('/admin/navigacija/changeView?itemId=' + id, function (data) {
                });
            }
            else {
                $(this).children().removeClass('fa-eye');
                $(this).children().addClass('fa-eye-slash');
                $.get('/admin/navigacija/changeView?itemId=' + id, function (data) {
                });
            }
        });

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
                    $.get('/admin/navigacija/deleteRow?itemId=' + id, function(data) {});
                    row.parent().parent().remove();
                    swal.fire("Ištrinta!", "Pasirinktas meniu punktas ištrintas.", "success");
                })()
            }
        })
    })  
    </script>
@endsection