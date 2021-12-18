@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Užsiregistravę stebėtojai
            </h1>
        </section>
        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <div class="col-md-offset-7 col-md-5">
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
                    <table class="table table-bordered">
                        <tr class="alert alert-success">
                            <th>Atsiskaitymas</th>
                            <th>Laikas</th>
                            <th>Srautas</th>
                            <th>Vardas, pavardė</th>
                            <th>Padalinys</th>
                            <th>Kontaktinė info</th>
                            <th>Statusas</th>
                            <th>Registracijos laikas</th>
                            <th>Veiksmas</th>
                        </tr>
                        @foreach($zmones as $zmogus)
                            <tr>
                                <td>{{$zmogus->subject_name}}</td>
                                <?php $times = explode('|', $zmogus->time)?>
                                <td>
                                    @foreach($times as $time)
                                        @if(strlen($time)>5)
                                            {{$time}}<br/>
                                        @endif
                                    @endforeach
                                </td>
                                <td>{{$zmogus->flow}}</td>
                                <td>{{$zmogus->name_p}}</td>
                                <td>{{$zmogus->padalinys_p}}</td>
                                <td>{{$zmogus->contact_p}}</td>
                                <td>{{$zmogus->status_p}}</td>
                                <td>{{$zmogus->dateRegistered}}</td>
                                <td>
                                    <a href="/admin/examPeople/{{$zmogus->id_p}}/edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    &nbsp;
                                    <a id="{{$zmogus->id_p}}" class="deleteRow" aria-hidden="true">
                                        <i
                                        class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                    {!! $zmones->appends(['sort' => 'id'])->links() !!}
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
                    axios.post("{{ route('pages.admin.examPeople.destroy', '', false)}}", {
                            id: id
                        });
                    row.parent().parent().remove();
                    swal.fire("Ištrinta!", "Užsiregistravęs stebėjotas ištrintas.", "success");
                })()
            }
        })
        })
    </script>
@endsection