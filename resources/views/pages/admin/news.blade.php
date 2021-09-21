@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                {!! $currentRoute == 'admin/naujienosLT' ? 'Naujienos LT': '' !!}
                {!! $currentRoute == 'admin/naujienosEN' ? 'Naujienos EN': '' !!}
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/naujienosLT' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li class="active">Naujienos LT</li>': '' !!}
                {!! $currentRoute == 'admin/naujienosEN' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li class="active">Naujienos EN</li>': '' !!}
            </ol>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <div class="d-flex justify-content-between">
                        <a class="btn btn-success" href="/admin/naujienos/prideti">Pridėti naujieną</a>
                        {{ Form::open(['method' => 'GET'])}}
                        <div class="input-group">
                            {{ Form::text('searchText', $searchText, array('class'=>'form-control', 'placeholder'=>'Paieška pagal pavadinimą')) }}

                            <span class="input-group-btn">
                            {{Form::submit('Ieškoti',['class'=>'btn btn-default'])}}
                            </span>
                            {{ Form::close() }}
                       
                        </div>
                    </div>
                    <br/>

                    <div>
                        @if (Session::has('message'))
                            <div class="alert alert-info" role="alert">{{ Session::get('message') }}</div>
                        @endif
                    </div>

                    <table class="table">
                        <tr class="alert alert-warning">
                            <th>Pavadinimas</th>
                            @if($currentRoute == 'admin/naujienosEN')
                                <th>LT pavadinimas</th>
                            @endif
                            <th>Data</th>
                            <th>Nuotrauka</th>
                            <th>Juodraštis</th>
                            <th>Ar rodoma vusa.lt</th>
                            <th>Veiksmai</th>
                        </tr>
                        @foreach ($news as $new)
                            <tr class="alert alert-success">
                                <td>{{ $new['title']}}</td>
                                @if($currentRoute == 'admin/naujienosEN')
                                    <td>{{ $new['title_lt']}}</td>
                                @endif
                                <td>{{ substr($new['publish_time'], 0, -3) }}</td>
                                <td class="hover_img">
                                    <a href="#">Nuotrauka
                                        <span> <img src="{{ $new['image']}}" height="100" alt="nera"/> </span>
                                    </a>
                                </td>
                                <td>
                                    {!! $new['draft'] == '1' ? 'Taip': 'Ne' !!}
                                </td>
                                <td>
                                    {!! $new['important'] == '1' ? 'Taip': 'Ne' !!}
                                </td>
                                <td>
                                    <a href="/admin/naujienos/{{$new['permalink']}}/redaguoti">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    &nbsp;
                                    <a id="{{$new['id']}}" class="deleteRow" aria-hidden="true">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                    {!! $news->appends(['sort' => 'publish_time', 'searchText'=>$searchText])->links() !!}
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
                    $.get('/admin/naujienos/deleteRow?itemId=' + id, function(data) {});
                    row.parent().parent().remove();
                    swal.fire("Ištrinta!", "Pasirinkta naujiena ištrinta.", "success");
                })()
            }
        })
    })
    </script>
@endsection