@extends('layouts.user.master')

@if(Lang::locale() == 'lt')
    @section('title', 'Darbotvarkė')
@else
    @section('title', 'Agenda')
@endif

@section('content')
    <div class="container agenda" id="infoPage">
        @if(Lang::locale() == 'lt')
            <div class="pageTitle">Darbotvarkė</div>
        @else
            <div class="pageTitle">Agenda</div>
        @endif

        @if(sizeof($agendas_today) == 0 && sizeof($agendas_comming) == 0)
            <dl class="dl" style="margin: 10px;">
                Darbotvarkės įrašų nėra.
            </dl>
        @endif

        <table class="table">
            @for($index = 0; $index < sizeof($agendas_today); $index++)
                @if($index > 0 && $agendas_today[$index]['date'] === $agendas_today[$index-1]['date'])
                    <tr class="no-border">
                        <td></td>
                        <td>{{$agendas_today[$index]['title']}}</td>
                    </tr>
                @else
                    <tr>
                        <td class="date"><span style="font-family: MYRIADPRO-BLACK;">Šiandien</span></td>
                        <td>{{$agendas_today[$index]['title']}}</td>
                    </tr>
                @endif
            @endfor

            @for($index = 0; $index < sizeof($agendas_comming); $index++)
                @if($index > 0 && $agendas_comming[$index]['date'] === $agendas_comming[$index-1]['date'])
                    <tr class="no-border">
                        <td></td>
                        <td>{{$agendas_comming[$index]['title']}}</td>
                    </tr>
                @else
                    <tr>
                        <td class="date"><span style="font-family: MYRIADPRO-BLACK;">{{$agendas_comming[$index]['date']}}</span></td>
                        <td>{{$agendas_comming[$index]['title']}}</td>
                    </tr>
                @endif
            @endfor
            <tr>
                <td colspan="2" style="font-family: MYRIADPRO-BLACK;"><br/>Ankstesni įvykiai:</td>
            </tr>
            @for($index = 0; $index < sizeof($agendas_past); $index++)
                @if($index > 0 && $agendas_past[$index]['date'] === $agendas_past[$index-1]['date'])
                    <tr class="no-border">
                        <td></td>
                        <td>{{$agendas_past[$index]['title']}}</td>
                    </tr>
                @else
                    <tr>
                        <td class="date"><span style="font-family: MYRIADPRO-BLACK;">{{$agendas_past[$index]['date']}}</span></td>
                        <td>{{$agendas_past[$index]['title']}}</td>
                    </tr>
                @endif
            @endfor
        </table>
        <div id="expiredEvends"></div>
        <button id="AddAgendaContent" class="btn btn-default center-block" onclick="getAgendaContent(2)" style="margin-top: 20px">Daugiau ankstesnių įvykių</button>
    </div>

    <script>
        function getAgendaContent(page) {
            $.ajax({
                url: '/lt/darbotvarke-ajax?page=' + page
            }).done(function (data) {
                htmlContent = '<table class="table">';
                for (i = 0; i < 10; i++) {
                    if (i > 0 && data['data'][i]['date'] === data['data'][i - 1]['date'])
                        htmlContent += '<tr class="no-border"> <td></td><td>' + data['data'][i]['title'] + '</td></tr>';
                    else
                        htmlContent += '<tr><td class="date"><span style="font-family: MYRIADPRO-BLACK;">' + data['data'][i]['date'] + '</span></td><td>' + data['data'][i]['title'] + '</td></tr>';
                }
                htmlContent += '</table>';
                var div = document.getElementById('expiredEvends');
                div.innerHTML += htmlContent;
            });
            page += 1
            document.getElementById('AddAgendaContent').setAttribute('onclick', 'getAgendaContent(' + page.toString() + ')');
        }
    </script>
@endsection