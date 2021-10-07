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
                                    <?php $yra1 = false; $yra1Count = 0; $index1 = 0 ?>
                                    @foreach ($navLevel1 as $row1)
                                        @if ('0' == $row1->pid)
                                            <?php $yra1 = true; $yra1Count++; ?>
                                        @endif
                                    @endforeach
                                    @if($yra1)
                                        @foreach ($navLevel1 as $row1)
                                            <?php $index1++; ?>
                                            <tr class="alert alert-success" id="{{ $row1->pid }}">
                                                <td colspan="2">{{ $row1->text }}</td>
                                                <td></td>
                                                <td></td>
                                                <td>{{ $row1->url }}</td>
                                                <td>
                                                    @if ($row1->show == 1)
                                                        <a id="{{$row1->id}}" class="changeView"
                                                              aria-hidden="true">
                                                              <i class="fas fa-eye"></i>
                                                            </a>
                                                    @else
                                                        <a id="{{$row1->id}}" class="changeView"
                                                              aria-hidden="true">
                                                              <i class="fas fa-eye-slash"></i>
                                                        </a>
                                                    @endif
                                                    &nbsp;
                                                    <a href="/admin/navigacija/{{$row1->id}}/redaguoti">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    &nbsp;
                                                    <a id="{{$row1->id}}" class="deleteRow" aria-hidden="true">
                                                        <i
                                                        class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                                <td>
                                                    @if ($row1->order != 0)
                                                        <a href="/admin/navigacija/swap/{{$row1->id}}/up">
                                                            <i class="fas fa-chevron-up"></i>
                                                        </a>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($index1 != $yra1Count)
                                                        <a href="/admin/navigacija/swap/{{$row1->id}}/down">
                                                            <i class="fas fa-chevron-down"></i>
                                                        </a>
                                                    @endif
                                                </td>
                                            </tr>
                                            <?php $yra2 = false; $yra2Count = 0?>
                                            @foreach ($navLevel2 as $row2)
                                                @if ($row1->id == $row2->pid)
                                                    <?php $yra2 = true; $yra2Count++; ?>
                                                @endif
                                            @endforeach
                                            @if ($yra2)
                                                <?php $index2 = 0; ?>
                                                @foreach ($navLevel2 as $row2)
                                                    @if ($row1->id == $row2->pid)
                                                        <?php $index2++; ?>
                                                        <tr class="alert alert-warning" id="{{ $row2->pid }}">
                                                            <td style="padding-left:1.5rem" colspan="2">
                                                                {{ $row2->text }}
                                                            </td>
                                                            <td></td>
                                                            <td></td>
                                                            <td>{{ $row2->url }}</td>
                                                            <td>
                                                                @if ($row2->show == 1)
                                                                    <a id="{{$row2->id}}" class="changeView"
                                                                          aria-hidden="true">
                                                                          <i class="fas fa-eye-slash"></i>
                                                                        </a>
                                                                @else
                                                                    <a id="{{$row2->id}}" class="changeView"
                                                                          aria-hidden="true">
                                                                    <i class="fas fa-eye"></i>
                                                                        </a>
                                                                @endif
                                                                &nbsp;
                                                                <a href="/admin/navigacija/{{$row2->id}}/redaguoti">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                &nbsp;
                                                                <a id="{{$row2->id}}" class="deleteRow" aria-hidden="true">
                                                                    <i
                                                                    class="fas fa-trash"></i>
                                                                </a>
                                                            </td>
                                                            <td>
                                                                @if ($row2->order != 0)
                                                                    <a href="/admin/navigacija/swap/{{$row2->id}}/up">
                                                                        <i class="fas fa-chevron-up"></i>
                                                                    </a>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if ($index2 != $yra2Count)
                                                                    <a href="/admin/navigacija/swap/{{$row2->id}}/down">
                                                                        <i class="fas fa-chevron-down"></i>
                                                                    </a>
                                                                @endif
                                                            </td>
                                                        </tr>

                                                        <?php $yra3 = false; $yra3Count = 0?>
                                                        @foreach ($navLevel3 as $row3)
                                                            @if ($row2->id == $row3->pid)
                                                                <?php $yra3 = true; $yra3Count++; ?>
                                                            @endif
                                                        @endforeach
                                                        @if ($yra3)
                                                            <?php $index3 = 0; ?>
                                                            @foreach ($navLevel3 as $row3)
                                                                @if ($row2->id == $row3->pid)
                                                                    <?php $index3++; ?>
                                                                    <tr class="alert alert-danger" id="{{ $row3->pid}}">
                                                                        <td style="padding-left:2.25rem" colspan="2">{{ $row3->text }}</td>
                                                                        <td></td>
                                                                        <td></td>
                                                                        <td>{{ $row3->url }}</td>
                                                                        <td>
                                                                            @if ($row3->show == 1)
                                                                                <a style="text-decoration:none" id="{{$row3->id}}" class="changeView"
                                                                                      aria-hidden="true">
                                                                                      <i class="fas fa-eye-slash"></i>
                                                                                    </a>
                                                                            @else
                                                                                <a style="text-decoration:none" id="{{$row3->id}}" class="changeView"
                                                                                      aria-hidden="true">
                                                                                      <i class="fas fa-eye"></i>
                                                                                    </a>
                                                                            @endif
                                                                            &nbsp;
                                                                            <a style="text-decoration:none" href="/admin/navigacija/{{$row3->id}}/redaguoti">
                                                                                <i class="fas fa-edit"></i>
                                                                            </a>
                                                                            &nbsp;
                                                                    <a id="{{$row3->id}}" class=" deleteRow" aria-hidden="true">
                                                                        <i
                                                                        class="fas fa-trash"></i>
                                                                    </a>
                                                                        </td>
                                                                        <td>
                                                                            @if ($row3->order != 0)
                                                                                <a href="/admin/navigacija/swap/{{$row3->id}}/up">
                                                                                    <i class="fas fa-chevron-up"></i>
                                                                                </a>
                                                                            @endif
                                                                        </td>
                                                                        <td>
                                                                            @if ($index3 != $yra3Count)
                                                                                <a href="/admin/navigacija/swap/{{$row3->id}}/down">
                                                                                    <i class="fas fa-chevron-up"></i>
                                                                                </a>
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                    <?php $yra4 = false; $yra4Count = 0; ?>

                                                                    @foreach ($navLevel4 as $row4)
                                                                        @if ($row3->id == $row4->pid)
                                                                            <?php $yra4 = true; $yra4Count++; ?>
                                                                        @endif
                                                                    @endforeach
                                                                    @if($yra4)
                                                                        <?php $index4 = 0; ?>
                                                                        @foreach ($navLevel4 as $row4)
                                                                            @if ($row3->id == $row4->pid)
                                                                                <?php $index4++; ?>
                                                                                <tr class="alert alert-info" id="{{ $row4->pid }}">
                                                                                    <td style="padding-left:3rem" colspan="2">{{ $row4->text }}</td>
                                                                                    <td></td>
                                                                                    <td></td>
                                                                                    <td>{{ $row4->url }}</td>
                                                                                    <td>
                                                                                        @if ($row4->show == 1)
                                                                                            <a id="{{$row4->id}}"
                                                                                                  class="changeView"
                                                                                                  aria-hidden="true">
                                                                                                  <i class="fas fa-eye-slash"></i></a>
                                                                                        @else
                                                                                            <a id="{{$row4->id}}"
                                                                                                  class="changeView"
                                                                                                  aria-hidden="true">
                                                                                                  <i class="fas fa-eye"></i></a>
                                                                                        @endif
                                                                                        &nbsp;
                                                                                        <a href="/admin/navigacija/{{$row4->id}}/redaguoti">
                                                                                            <i class="fas fa-edit"></i>
                                                                                        </a>
                                                                                        &nbsp;
                                                                                <a id="{{$row4->id}}" class="deleteRow"
                                                                                      aria-hidden="true">
                                                                                      <i
                                                                                      class="fas fa-trash"></i>
                                                                                    </a>
                                                                                    </td>
                                                                                    <td>
                                                                                        @if ($row4->order != 0)
                                                                                            <a href="/admin/navigacija/swap/{{$row4->id}}/up">
                                                                                                <i class="fas fa-chevron-up"></i>
                                                                                            </a>
                                                                                        @endif
                                                                                    </td>
                                                                                    <td>
                                                                                        @if ($index4 != $yra4Count)
                                                                                            <a href="/admin/navigacija/swap/{{$row4->id}}/down">
                                                                                                <i class="fas fa-chevron-down"></i>
                                                                                            </a>
                                                                                        @endif
                                                                                    </td>
                                                                                </tr>
                                                                            @endif
                                                                        @endforeach
                                                                    @endif
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                    @endif
                                                @endforeach
                                            @endif
                                        @endforeach
                                    @endif
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