@extends('layouts.user.master')

@section('title'){{'Studentų registracija į poilsio namus Pervalkoje ir Palangoje'}}@endsection

@section('meta')
    <meta property="og:url" content="{{"http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']}}"/>
    <meta property="og:type" content="website"/>
    <meta property="og:title" content="VU SA | Studentų registracija į poilsio namus Pervalkoje ir Palangoje"/>
    <meta property="og:description" content=""/>
    <meta property="og:image" content="/images/icons/logos/vusa.lin.hor.png"/>
@endsection

@section('content')
    <div class="container" id="infoPage">
        <div class="pageTitle">Studentų registracija į poilsio namus Pervalkoje ir Palangoje</div>
        <div class="col-lg-10 infoPage" id="infoPageText">

            <div id="result"></div>

            {!! Form::open(array('id'=>'registrationForm')) !!}

            <div class="col-lg-6">
                <div class="form-group">
                    {{ Form::label('place', 'Vieta *') }}
                    {{ Form::select('place',
                        array('palanga' => 'Palanga',
                              'pervalka' => 'Pervalka'),
                              '', array('class'=>'form-control')
                              )}}
                </div>

                <div class="form-group">
                    {{ Form::label('phone', 'Telefono nr. *') }}
                    {{ Form::text('phone', '', array('class'=>'form-control')) }}
                </div>

                <div class="form-group">
                    {{ Form::label('unit', 'Padalinys *') }}
                    {{ Form::text('unit', '', array('class'=>'form-control')) }}
                </div>

                <div class="form-group">
                    {{ Form::label('date', 'Laikotarpis *') }}
                    {{ Form::select('date', array(), '', array('id' => 'date','class'=>'form-control'))}}
                </div>

                <div class="form-group">
                    {{ Form::label('people', 'Asmenų skaičius *') }}
                    {{ Form::number('people', '0', array('class'=>'form-control')) }}
                </div>

                <div class="form-group">
                    {{ Form::label('people_2m', 'Vaikų skaičius ir amžius iki 2m. *') }}
                    {{ Form::number('people_2m', '0', array('class'=>'form-control')) }}
                </div>
            </div>

            <div class="col-lg-offset-1 col-lg-5">
                <div class="form-group">
                    {{ Form::label('name', 'Vardas ir pavardė *') }}
                    {{ Form::text('name', '', array('class'=>'form-control')) }}
                </div>

                <div class="form-group">
                    {{ Form::label('email', 'El. paštas *') }}
                    {{ Form::email('email', '', array('class'=>'form-control')) }}
                </div>

                <div class="form-group">
                    {{ Form::label('room', 'Kambario tipas *') }}
                    {{ Form::select('room', array('2'=>'Dvivietis','3' => 'Trivietis','4' => 'Keturvietis'),'' , array('class'=>'form-control'))}}
                </div>

                <div class="form-group">
                    {{ Form::label('animals', 'Gyvūnų skaičius *') }}
                    {{ Form::number('animals', '0', array('class'=>'form-control')) }}
                </div>

                <div class="form-group">
                    {{ Form::label('people_18m', 'Vaikų skaičius ir amžius iki 18m. *') }}
                    {{ Form::number('people_18m', '0', array('class'=>'form-control')) }}
                </div>
            </div>
            <div class="col-lg-12">
                <div class="form-group">
                    {{ Form::label('notes', 'Pastabos ir pageidavimai') }}
                    {{ Form::textarea('notes', '', array('class'=>'form-control edit')) }}
                </div>
            </div>

            {{Form::submit('Užsiregistruoti', ['class'=>'btn btn-primary message col-lg-offset-1 col-lg-10', 'id'=>'submit'])}}
            {{ Form::close() }}
        </div>
    </div>

<!--Start of Tawk.to Script-->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/5f71b135f0e7167d00145612/default';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
<!--End of Tawk.to Script-->

    <script>
        $(window).bind('load', function (e) {
            var data = [];
            if ($("#place").val() == 'palanga') {
                data[0] = [2, 'Dvivietis'];
                data[1] = [3, 'Trivietis'];
                data[2] = [4, 'Keturvietis'];
            }
            else if ($("#place").val() == 'pervalka') {
                data[0] = [4, 'Keturvietis'];
            }
            $('#room').empty();
            $.each(data, function (index) {
                $('#room').append('<option value="' + data[index][0] + '">' + data[index][1] + '</option>');
            });

            $.get('/lt/registracija-i-poilsio-namus-diena?room=' + $("#room").val() + '&place=' + $("#place").val(), function (data) {
                $('#date').empty();
                $.each(data, function (index) {
                    if ($("#room").val() == '2')
                        if (data[index][1] >= 1 || data[index][2] == 0)
                            $('#date').append('<option value="' + index + '" disabled>' + data[index][0] + '</option>');
                        else
                            $('#date').append('<option value="' + index + '">' + data[index][0] + '</option>');

                    else if ($("#room").val() == '3')
                        if (data[index][1] >= 1 || data[index][3] == 0)
                            $('#date').append('<option value="' + index + '" disabled>' + data[index][0] + '</option>');
                        else
                            $('#date').append('<option value="' + index + '">' + data[index][0] + '</option>');

                    else if ($("#room").val() == '4')
                        if (data[index][1] >= 1 || data[index][4] == 0)
                            $('#date').append('<option value="' + index + '" disabled>' + data[index][0] + '</option>');
                        else
                            $('#date').append('<option value="' + index + '">' + data[index][0] + '</option>');
                });
            });
        });

        $('#place').on('change', function (e) {
            var data = [];
            if ($("#place").val() == 'palanga') {
                data[0] = [2, 'Dvivietis'];
                data[1] = [3, 'Trivietis'];
                data[2] = [4, 'Keturvietis'];
            }
            else if ($("#place").val() == 'pervalka') {
                data[0] = [4, 'Keturvietis'];
            }
            $('#room').empty();
            $.each(data, function (index) {
                $('#room').append('<option value="' + data[index][0] + '">' + data[index][1] + '</option>');
            });

            $.get('/lt/registracija-i-poilsio-namus-diena?room=' + $("#room").val() + '&place=' + $("#place").val(), function (data) {
                $('#date').empty();
                $.each(data, function (index) {
                    if ($("#room").val() == '2')
                        if (data[index][1] >= 1 || data[index][2] == 0)
                            $('#date').append('<option value="' + index + '" disabled>' + data[index][0] + '</option>');
                        else
                            $('#date').append('<option value="' + index + '">' + data[index][0] + '</option>');

                    else if ($("#room").val() == '3')
                        if (data[index][1] >= 1 || data[index][3] == 0)
                            $('#date').append('<option value="' + index + '" disabled>' + data[index][0] + '</option>');
                        else
                            $('#date').append('<option value="' + index + '">' + data[index][0] + '</option>');

                    else if ($("#room").val() == '4')
                        if (data[index][1] >= 1 || data[index][4] == 0)
                            $('#date').append('<option value="' + index + '" disabled>' + data[index][0] + '</option>');
                        else
                            $('#date').append('<option value="' + index + '">' + data[index][0] + '</option>');

                });
            });
        });

        $('#room').on('change', function (e) {
            $.get('/lt/registracija-i-poilsio-namus-diena?room=' + $("#room").val() + '&place=' + $("#place").val(), function (data) {
                $('#date').empty();
                $.each(data, function (index) {
                    if ($("#room").val() == '2')
                        if (data[index][1] >= 1 || data[index][2] == 0)
                            $('#date').append('<option value="' + index + '" disabled>' + data[index][0] + '</option>');
                        else
                            $('#date').append('<option value="' + index + '">' + data[index][0] + '</option>');

                    else if ($("#room").val() == '3')
                        if (data[index][1] >= 1 || data[index][3] == 0)
                            $('#date').append('<option value="' + index + '" disabled>' + data[index][0] + '</option>');
                        else
                            $('#date').append('<option value="' + index + '">' + data[index][0] + '</option>');

                    else if ($("#room").val() == '4')
                        if (data[index][1] >= 1 || data[index][4] == 0)
                            $('#date').append('<option value="' + index + '" disabled>' + data[index][0] + '</option>');
                        else
                            $('#date').append('<option value="' + index + '">' + data[index][0] + '</option>');
                });
            });
        });

        $("#registrationForm").submit(function (event) {
            event.preventDefault();
            var $form = $(this),
                    url = $form.attr("action"),
                    place = $form.find("#place").val(),
                    phone = $form.find("#phone").val(),
                    unit = $form.find("#unit").val(),
                    date = $form.find("#date").val(),
                    people = $form.find("#people").val(),
                    people_2m = $form.find("#people_2m").val(),
                    name = $form.find("#name").val(),
                    email = $form.find("#email").val(),
                    room = $form.find("#room").val(),
                    animals = $form.find("#animals").val(),
                    people_18m = $form.find("#people_18m").val(),
                    notes = $form.find("#notes").val();

            var posting = $.post(url, {
                place: place,
                phone: phone,
                unit: unit,
                date: date,
                people: people,
                people_2m: people_2m,
                name: name,
                email: email,
                room: room,
                animals: animals,
                people_18m: people_18m,
                notes: notes
            });

            posting.done(function (data) {
                $("html, body").animate({scrollTop: 0}, "fast");
                if (data == 'OK')
                    msg = '<div class="alert alert-success" role="alert">Jūsų registracija sėkminga. Informaciją dėl apmokėjimo gausite, kai registracija bus uždaryta.</div>';
                else {
                    msg = '<div class="alert alert-danger" role="alert"><ul>';

                    $.each(data, function (index) {
                        if (data[index] == "The name field is required.") {
                            msg += '<li>Vardo ir pavardės laukas yra neužpildytas.</li>';
                        }
                        else if (data[index] == "The phone field is required.") {
                            msg += '<li>Telefono nr. laukas yra neužpildytas.</li>';
                        }
                        else if (data[index] == "The email field is required.") {
                            msg += '<li>El. pašto laukas yra neužpildytas.</li>';
                        }
                        else if (data[index] == "The email has already been taken.") {
                            msg += '<li>Toks el. pašto adresas jau egzistuoja.</li>';
                        }
                        else if (data[index] == "The unit field is required.") {
                            msg += '<li>Padalinio laukas yra neužpildytas.</li>';
                        }
                        else if (data[index] == "The people field is required.") {
                            msg += '<li>Asmenų skaičiaus laukas yra neužpildytas.</li>';
                        }
                        else if (data[index] == "The people 2m field is required.") {
                            msg += '<li> Vaikų skaičius ir amžius iki 2m. laukas yra neužpildytas.</li>';
                        }
                        else if (data[index] == "The animals field is required.") {
                            msg += '<li>Gyvūnų skaičiaus laukas yra neužpildytas.</li>';
                        }
                        else if (data[index] == "The people 18m field is required.") {
                            msg += '<li>Vaikų skaičius ir amžius iki 18m. laukas yra neužpildytas</li>';
                        }
                        else if (data[index] == "The month from field is required.") {
                            msg += '<li>Nepasirinktas mėnuo nuo kada registrojatės</li>';
                        }
                        else if (data[index] == "The month till field is required.") {
                            msg += '<li>Nepasirinktas mėnuo iki kada registrojatės</li>';
                        }
                        else {
                            msg += '<li>' + data[index] + '</li>';
                        }
                    });
                    msg += "</ul></div>";
                }
                $("#result").empty().append(msg);
            });
        });
    </script>
@endsection
