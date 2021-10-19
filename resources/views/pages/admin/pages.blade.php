@extends('layouts.admin.master')

@section('content')

    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                {!! $currentRoute == 'admin/puslapiaiLT' ? 'Puslapiai LT' : '' !!}
                {!! $currentRoute == 'admin/puslapiaiEN' ? 'Puslapiai EN' : '' !!}
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/puslapiaiLT' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li>
                <li class="active">Puslapiai LT</li>' : '' !!}
                {!! $currentRoute == 'admin/puslapiaiEN' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li>
                <li class="active">Puslapiai EN</li>' : '' !!}
            </ol>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <div class="d-flex justify-content-between">
                        <a class="btn btn-success" href="/admin/puslapiai/prideti">Pridėti puslapį</a>
                        {{ Form::open(['method' => 'GET']) }}
                        <div class="input-group">
                            {{ Form::text('searchText', $searchText, ['class' => 'form-control', 'placeholder' => 'Paieška pagal pavadinimą']) }}

                            <span class="input-group-btn">
                                {{ Form::submit('Ieškoti', ['class' => 'btn btn-default']) }}
                            </span>
                            {{ Form::close() }}
                        </div>
                    </div>
                    <br>
                    <table class="table">
                        <tr class="alert alert-warning">
                            <th>Pavadinimas</th>
                            {{-- @if ($currentRoute == 'admin/puslapiaiEN')
                                <th>LT pavadinimas</th>
                            @endif --}}
                            <th>Kategorija</th>
                            <th>Nuoroda</th>
                            <th>Veiksmai</th>
                        </tr>
                        @foreach ($pages as $page)
                            <tr class="alert alert-success">
                                <td>{{ $page['title'] }}</td>
                                {{-- @if ($currentRoute == 'admin/puslapiaiEN')
                                    <td>{{ $page['title_lt'] }}</td>
                                @endif --}}
                                <td>
                                    {!! $page['category'] == '1' ? 'Akademinė informacija' : '' !!}
                                    {!! $page['category'] == '2' ? 'Socialinė informacija' : '' !!}
                                    {!! $page['category'] == '3' ? 'Kita informacija' : '' !!}
                                </td>
                                <td> <a target="_blank" 
                                    href="{{ $page->alias == 'admin' 
                                    ? '/' . $page->lang . '/' .  $page['permalink']
                                    : 'http://' .  substr($page->alias, 4) . '.' . request()->getHttpHost() . '/' . $page->lang . '/' .  $page['permalink'] }} ">/{{ $page->lang }}/{!! $page["permalink"] !!} </td>
                                <td>
                                    @if ($page['disabled'] == 1)
                                        <a style="text-decoration:none" id="{{ $page['id'] }}" class="changeView"
                                            aria-hidden="true">
                                        <i class="fas fa-eye-slash"></i>
                                        </a>
                                    @else
                                        <a style="text-decoration:none" id="{{ $page['id'] }}" class="changeView"
                                            aria-hidden="true">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @endif
                                    &nbsp;
                                    @if ($sessionInfo->gid == 1)
                                        <?php $permalinkArr = explode('/', $page['permalink']); ?>
                                        <a style="text-decoration:none" href="/admin/puslapiai/{{ $page['permalink'] }}/redaguoti">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @else
                                        <a style="text-decoration:none" href="/admin/puslapiai/{{ $page['permalink'] }}/redaguoti">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif
                                    &nbsp;
                                    <a id="{{ $page['id'] }}" class="deleteRow" aria-hidden="true"><i
                                            class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </table>

                    {{ $pages->appends(['sort' => 'title', 'searchText' => $searchText])->links() }}
                </div>
            </div>
        </section>
    </div>

    <script>
        $('.changeView').on('click', function(e) {
            var id = $(this).attr('id');

            if ($(this).children().hasClass('fa-eye-slash')) {
                $(this).children().removeClass('fa-eye-slash');
                $(this).children().addClass('fa-eye');
                $.get('/admin/puslapiai/changeView?itemId=' + id, function(data) {});
            } else {
                $(this).children().removeClass('fa-eye');
                $(this).children().addClass('fa-eye-slash');
                $.get('/admin/puslapiai/changeView?itemId=' + id, function(data) {});
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
                        $.get('/admin/puslapiai/deleteRow?itemId=' + id, function(data) {});
                        row.parent().parent().remove();
                        swal.fire("Ištrinta!", "Pasirinktas puslapis ištrintas.", "success");
                    })()
                }
            })
        })

    </script>
    @endsection
