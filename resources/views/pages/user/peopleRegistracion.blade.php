@extends('layouts.user.master')

@section('title'){{'Egzamino ar koliokviumo stebėjimo registracijos forma'}}@endsection

@section('meta')
    <meta property="og:url" content="{{"http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']}}"/>
    <meta property="og:type" content="website"/>
    <meta property="og:title" content="VU SA | Egzamino ar koliokviumo stebėjimo registracijos forma"/>
    <meta property="og:description" content=""/>
    <meta property="og:image" content="/images/icons/logos/vusa.lin.hor.png"/>
@endsection

@section('content')
    <div class="container" id="infoPage">
        <div class="pageTitle">Atsiskaitymo stebėtojo registracijos forma</div>
        <div class="col-lg-10 infoPage" id="infoPageText">
            @if($atsiskaitymas != null)
                <p>{{$atsiskaitymas['subject_name']}} ({{$atsiskaitymas['exam']}})</p>

                <p>Data (srautas):<br/>
                    <?php $timeArr = explode('|', $atsiskaitymas->time); $i = 0; $index = 0; ?>
                    @foreach($timeArr as $time)
                        <?php $i++; ?>
                        @if(strlen($time) > 2)
                            {{$i.'. '.$time}}<br/>
                            <?php $index += 1; ?>
                        @endif
                    @endforeach
                </p>

                <p>Trukmė: {{$atsiskaitymas['duration']}}</p>

                <p style="font-family: MYRIADPRO-BOLD">Atsiskaitymų stebėjimui savo padalinyje studentai negali registruotis.</p>

                <div id="result"></div>

                {!! Form::open(array('id'=>'registrationForm')) !!}

                <div class="col-lg-6">
                    <div class="form-group">
                        {{ Form::hidden('uuid', $atsiskaitymas['uuid'], array('id' => 'uuid')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('name_p', 'Vardas ir pavardė *') }}
                        {{ Form::text('name_p', '', array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('padalinys', 'Padalinys *') }}
                        {{ Form::select('padalinys', array('-'=>'-- Padalinys --','chgf'=>'CHGF','evaf' => 'EVAF', 'ff'=>'FF', 'filf'=>'FiLF', 'fsf'=>'FsF', 'gmc'=>'GMC', 'if'=>'IF', 'kf'=>'KF', 'knf'=> 'KnF', 'mf'=> 'MF',
                    'mif'=>'MIF', 'sa'=>'SA', 'tf'=> 'TF', 'tspmi'=> 'TSPMI', 'vm'=> 'VM'),'', array('class'=>'form-control'))}}
                    </div>

                    @if($index != 1)
                        <div class="form-group">
                            {{ Form::label('flow', 'Srautas *') }}
                            {{ Form::number('flow', '', array('class'=>'form-control','min'=>1,'max'=>10)) }}
                        </div>
                    @else
                        {{ Form::hidden('flow', '1', array('id' => 'flow')) }}
                    @endif

                    <div class="form-group">
                        {{ Form::label('contact_p', 'Kontaktinė informacija (telefono nr.) *') }}
                        {{ Form::text('contact_p', '', array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                {{ Form::checkbox('acceptGDPR', '1', null, array("id" => "acceptGDPR")) }} 
                                Susipažinau su <a id="rulesLink" target="_blank" href="/uploads/Dokumentų šablonai/Asmens_duomenu_tvarkymo_VUSA_tvarkos_aprasas.pdf">Asmens duomenų tvarkymo Vilniaus universiteto Studentų atstovybėje tvarkos aprašu</a> ir sutinku.
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                            {{ Form::checkbox('acceptDataManagement', '1', null, array("id" => "acceptDataManagement")) }} 
                            Sutinku, kad mano pateikti asmens duomenys būtų tvarkomi vidaus administravimo tikslu pagal Asmens duomenų tvarkymo Vilniaus universiteto Studentų atstovybėje tvarkos aprašą.
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div> Duomenų valdytojas yra Vilniaus universiteto Studentų atstovybė (adresas: Universiteto g. 3, Observatorijos kiemelis, Vilnius, tel.:, el. paštas: info@vusa.lt). Jūsų pateikti duomenys bus naudojami susisiekti su jumis. </div>
                        <br>
                        <div> Duomenų subjektas turi teisę susipažinti su savo asmens duomenimis, teisę reikalauti ištaisyti neteisingus, neišsamius, netikslius savo asmens duomenis ir kitas teisės aktais numatytas teises. Kilus klausimams ir norint realizuoti savo, kaip duomenų subjekto, teises, galite kreiptis į <a href="mailto:dap@vusa.lt">dap@vusa.lt</a>.</div>
                        <br>
                    </div>

                    {{Form::submit('Užregistruoti', ['class'=>'btn btn-primary message col-lg-offset-1 col-lg-10', 'id'=>'submit'])}}
                </div>

                {{ Form::close() }}
            @else
                <h3>Užregistruotų atsiskaitymų nėra, registracija - uždaryta.</h3>
            @endif
        </div>
    </div>

    <script type="text/javascript">
        $("#registrationForm").submit(function (event) {
            event.preventDefault();
            var $form = $(this),
                    url = $form.attr("action"),
                    uuid = $form.find("#uuid").val(),
                    name_p = $form.find("#name_p").val(),
                    padalinys = $form.find("#padalinys").val(),
                    contact_p = $form.find("#contact_p").val(),
                    flow = $form.find("#flow").val(),
                    acceptGDPR = document.getElementById('acceptGDPR'), 
                    acceptDataManagement = document.getElementById('acceptDataManagement');

            var posting = $.post(url, {
                uuid: uuid,
                name_p: name_p,
                padalinys: padalinys,
                contact_p: contact_p,
                flow: flow,
                acceptGDPR: acceptGDPR.checked,
                acceptDataManagement: acceptDataManagement.checked
            });

            posting.done(function (data) {
                $("html, body").animate({scrollTop: 0}, "fast");
                if (data == 'OK')
                    msg = '<div class="alert alert-success" role="alert">Ačiū, kad užsiregistravote atsiskaitymo stebėjimui ir prisidedate prie akademinės etikos puoselėjimo Vilniaus universitete <br/>' +
                            'Kilus klausimams galite kreiptis <a href="mailto:saziningai@vusa.lt">saziningai@vusa.lt</a>. arba į savo padalinio programos "Sąžiningai" koordinatorių</div>';
                else {
                    msg = '<div class="alert alert-danger" role="alert"><ul>';

                    $.each(data, function (index) {
                        if (data[index] == "The name p field is required.") {
                            msg += '<li>Vardo ir pavardės laukas yra neužpildytas.</li>';
                        }
                        else if (data[index] == "The contact p field is required.") {
                            msg += '<li>Kontaktinės informacijos laukas yra neužpildytas.</li>';
                        }
                        else if (data[index] == "The flow field is required.") {
                            msg += '<li>Srauto laukas yra neužpildytas.</li>';
                        }
                        else if (data[index] == "The accept g d p r must be accepted.") {
                            msg += '<li>Turite susipažinti su <a id="rulesLink" target="_blank" href="/uploads/Dokumentų šablonai/Asmens_duomenu_tvarkymo_VUSA_tvarkos_aprasas.pdf">VU SA asmens duomenų tvarkymo aprašu</a>.</li>';
                        }
                        else if (data[index] == "The accept data management must be accepted.") {
                            msg += '<li>Turite sutikti su asmens duomenų tvarkymu.</li>';
                        }
                        else {
                            msg += '<li>' + data[index] + '</li>';
                        }

                    });
                    msg += "</ul></div>";
                }
                $("#result").empty().append(msg);
                if (data == 'OK')
                    $("#registrationForm").hide();
            });
        });
    </script>
@endsection