@extends('layouts.user.master')

@section('title'){{'Egzamino ar kolokviumo stebėjimo registracijos forma'}}@endsection

@section('meta')
    <meta property="og:url" content="{{"http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']}}"/>
    <meta property="og:type" content="website"/>
    <meta property="og:title" content="VU SA | Egzamino ar kolokviumo stebėjimo registracijos forma"/>
    <meta property="og:description" content=""/>
    <meta property="og:image" content="/images/icons/logos/vusa.lin.hor.png"/>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
@endsection

@section('content')
    <div class="container" id="infoPage">
        <div class="pageTitle">Egzamino ar kolokviumo stebėjimo registracijos forma</div>
        <div class="col-lg-10 infoPage" id="infoPageText">
            <div id="result"></div>

            {!! Form::open(array('id'=>'registrationForm')) !!}

            <div class="alert alert-success success-yellow" role="alert">Prašome atsiskaitymą registruoti likus bent 3 d.d. iki jo pradžios, kad būtų laiku surasti stebėtojai. Kitu atveju, kreipkitės į saziningai@vusa.lt</div>

            <div class="col-lg-9">
                <div class="form-group">
                    {{ Form::label('name', 'Vardas ir pavardė') }}
                    {{ Form::text('name', '', array('class'=>'form-control')) }}
                </div>

                <div class="form-group">
                    {{ Form::label('contact', 'El. paštas *') }}
                    {{ Form::text('contact', '', array('class'=>'form-control')) }}
                </div>

                <div class="form-group">
                    {{ Form::label('phone', 'Telefono nr. *') }}
                    {{ Form::text('phone', '', array('class'=>'form-control', 'placeholder' => '+370xxxxxxxx')) }}
                </div>

                <div class="form-group">
                    {{ Form::label('exam', 'Atsiskaitymo pobūdis *') }}
                    {{ Form::select('exam', array('-'=>'-- Pobūdis --','koliokviumas'=>'Kolokviumas','egzaminas' => 'Egzaminas'),'' , array('class'=>'form-control'))}}
                </div>

                <div class="form-group">
                    {{ Form::label('padalinys', 'Atsiskaitymą laikančiųjų padalinys *') }}
                    {{ Form::select('padalinys', array('-'=>'-- Padalinys --','chgf'=>'CHGF','evaf' => 'EVAF', 'ff'=>'FF', 'filf'=>'FLF', 'fsf'=>'FsF', 'gmc'=>'GMC', 'if'=>'IF', 'kf'=>'KF', 'knf'=> 'KnF', 'mf'=> 'MF',
                    'mif'=>'MIF', 'sa'=>'ŠA', 'tf'=> 'TF', 'tspmi'=> 'TSPMI', 'vm'=> 'VM'),'', array('class'=>'form-control'))}}
                </div>

                <div class="form-group">
                    {{ Form::label('subject_name', 'Atsiskaitomo dalyko pavadinimas *') }}
                    {{ Form::text('subject_name', '', array('class'=>'form-control')) }}
                </div>

                <div class="form-group">
                    {{ Form::label('place', 'Atsiskaitymo vieta: padalinys ir auditorija *') }}
                    {{ Form::text('place', '', array('class'=>'form-control')) }}
                </div>

                <div class="form-group" id="timeInput">
                    {{ Form::label('time', 'Atsiskaitymo data ir laikas *') }}
                    {{ Form::text('time', '', array('class'=>'form-control', 'id'=>'time')) }}
                </div>

                <div class="form-group" id="addSrautasGr">
                    <a id="addSrautas" class="btn btn-default">Pridėti papildomą srautą</a>
                </div>

                <div class="form-group">
                    {{ Form::label('duration', 'Atsiskaitymo trukmė (jei laikoma srautais, parašyti srautų skaičių ir kiek laiko skiriama vienam srautui) *') }}
                    {{ Form::text('duration', '', array('class'=>'form-control')) }}
                </div>

                <div class="form-group">
                    {{ Form::label('count', 'Atsiskaitymą laikančių studentų skaičius *') }}
                    {{ Form::number('count', '', array('class'=>'form-control','min'=>1,'max'=>300)) }}
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

            </div>

            {{ Form::submit('Užregistruoti', ['class'=>'btn btn-primary message col-lg-offset-1 col-lg-10', 'id'=>'submit']) }}
            {{ Form::close() }}
        </div>
    </div>

    <script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.9.0/moment-with-locales.js"></script>
    <script src="//cdn.rawgit.com/Eonasdan/bootstrap-datetimepicker/e8bddc60e73c1ec2475f827be36e1957af72e2ea/src/js/bootstrap-datetimepicker.js"></script>
    
    <script type="text/javascript">
        var timesCounter = 1;
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        $("#addSrautas").click(function () {
            timesCounter++;
            if (timesCounter == 2)
                $('#timeInput').append('<br/><label for="time' + timesCounter + '">Antro srauto atsiskaitymo data, laikas * </label> <input class="form-control" id="time' + timesCounter +
                        '" name="time' + timesCounter + '" value="" type="text">');
            else if (timesCounter == 3)
                $('#timeInput').append('<br/><label for="time' + timesCounter + '">Trečio srauto atsiskaitymo data, laikas * </label> <input class="form-control" id="time' + timesCounter +
                        '" name="time' + timesCounter + '" value="" type="text">');
            else if (timesCounter == 4)
                $('#timeInput').append('<br/><label for="time' + timesCounter + '">Ketvirto srauto atsiskaitymo data, laikas * </label> <input class="form-control" id="time' + timesCounter +
                        '" name="time' + timesCounter + '" value="" type="text">');

            $('#time' + timesCounter).datetimepicker({
                locale: 'lt'
            });

            if (timesCounter == 4)
                $('#addSrautasGr').hide();
        });

        $("#registrationForm").submit(function (event) {

            event.preventDefault();

            var $form = $(this),
                    url = $form.attr("action"),
                    name = $form.find("#name").val(),
                    contact = $form.find("#contact").val(),
                    phone = $form.find("#phone").val(),
                    exam = $form.find("#exam").val(),
                    padalinys = $form.find("#padalinys").val(),
                    place = $form.find("#place").val(),
                    time = $form.find("#time").val(),
                    time2 = $form.find("#time2").val(),
                    time3 = $form.find("#time3").val(),
                    time4 = $form.find("#time4").val(),
                    duration = $form.find("#duration").val(),
                    subject_name = $form.find("#subject_name").val(),
                    count = $form.find("#count").val(),
                    acceptGDPR = document.getElementById('acceptGDPR'), 
                    acceptDataManagement = document.getElementById('acceptDataManagement');
            
            var posting = $.post(url, {
                name: name,
                contact: contact,
                phone: phone,
                exam: exam,
                padalinys: padalinys,
                place: place,
                time: time,
                time2: time2,
                time3: time3,
                time4: time4,
                duration: duration,
                subject_name: subject_name,
                count: count,
                acceptGDPR: acceptGDPR.checked,
                acceptDataManagement: acceptDataManagement.checked
            });

            posting.done(function (data) {
                $("html, body").animate({scrollTop: 0}, "fast");
                console.log(data);
                if (data == 'OK')
                    msg = '<div class="alert alert-success success-yellow" role="alert">Ačiū, jūsų atsiskaitymas užregistruotas. Jei turite papildomų klausimų, galite kreiptis el. paštu ' +
                            '<a href="mailto:saziningai@vusa.lt">saziningai@vusa.lt</a>. Norint registruotis atsiskaitymo stebėjimui, grįžkite į ' +
                            '<a href="/saziningai-uzregistruoti-egzaminai">atsiskaitymo registrą</a> ir pasirinkite norimą atsiskaitymą</a>.</div>';
                else {
                    msg = '<div class="alert alert-danger" role="alert"><ul>';

                    $.each(data, function (index) {
                        if (data[index] == "The name field is required.") {
                            msg += '<li>Vardo ir pavardės laukas yra neužpildytas.</li>';
                        }
                        else if (data[index] == "The phone field is required.") {
                            msg += '<li>Telefono nr. laukas yra neužpildytas.</li>';
                        }
                        else if (data[index] == "The contact field is required.") {
                            msg += '<li>El. paštas yra neužpildytas.</li>';
                        }
                        else if (data[index] == "The exam field is required.") {
                            msg += '<li>Atsiskaitymo pobūdžio laukas yra neužpildytas.</li>';
                        }
                        else if (data[index] == "The padalinys field is required.") {
                            msg += '<li>Atsiskaitymą laikančiųjų padalinio laukas yra neužpildytas.</li>';
                        }
                        else if (data[index] == "The place field is required.") {
                            msg += '<li>Atsiskaitymo vietos laukas yra neužpildytas.</li>';
                        }
                        else if (data[index] == "The time field is required.") {
                            msg += '<li>Atsiskaitymo laiko laukas yra neužpildytas arba .</li>';
                        }
                        else if (data[index] == "The time must be a date after +3 days.") {
                            msg += '<li>Nurodyta data yra anksčiau nei už 3 dienų. Primename, kad atsiskaitymus prašome registruoti likus 3 dienoms iki jų datos. Kitu atveju, kreipkites į saziningai@vusa.lt</li>'
                        }
                        else if (data[index] == "The duration field is required.") {
                            msg += '<li>Atsiskaitymo trukmės laukas yra neužpildytas.</li>';
                        }
                        else if (data[index] == "The subject name field is required.") {
                            msg += '<li>Atsiskaitymo dalyko pavadinimo laukas yra neužpildytas.</li>';
                        }
                        else if (data[index] == "The count field is required.") {
                            msg += '<li>Atsiskaitymą laikančių studentų laukas yra neužpildytas.</li>';
                        }
                        else if (data[index] == "The accept g d p r must be accepted.") {
                            msg += '<li>Turite susipažinti su <a id="rulesLink" target="_blank" href="/uploads/Dokumentų šablonai/Asmens_duomenu_tvarkymo_VUSA_tvarkos_aprasas.pdf">VU SA asmens duomenų tvarkymo aprašu</a>.</li>';
                        }
                        else if (data[index] == "The accept data management must be accepted.") {
                            msg += '<li>Turite sutikti su asmens duomenų tvarkymu.</li>';
                        }
                        else if (data[index] == "The contact must be a valid email address.") {
                            msg += '<li>Patikrinkite el. pašto laukelį.</li>';
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

        $('#time').datetimepicker({
            locale: 'lt'
        });
        
    </script>
@endsection