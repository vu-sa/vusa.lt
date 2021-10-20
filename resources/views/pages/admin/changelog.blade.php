@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1> Pasikeitimų sąrašas</h1>
        </section>
        <section class="content">
            <p>Šis puslapis skirtas aprašyti vusa.lt atnaujinimams.</p>

            <p>Stengsiuos šiuos atnaujinimus daryti bent savaitinius. Kitai savaitei bus dar visokių techninių
                aptvarkymų, todėl nieko tiksliai nenumatysiu, bet tam tikrų planelių yra, kaip pvz.: tiesioginis Analytics'ų
                rodymas, pilnas emoji palaikymas ir panašiai.</p>

            <p> Jeigu kas nors turi pastebėjimų, arba jum meta kokias nors klaidas, rašykit man, <a
                    href="mailto:it@vusa.lt">paštu</a>,
                arba jeigu kas nors kritiško tai ir į Messengerį. :> Idėjos irgi welcome,
                bet sąrašėlis ir taip yra nemažas.
            </p>


            <hr style="border-top: 1px solid #111">
            <h3> <strong>2021 m. spalio 20 d.</strong></h3>
            <p><strong>Po 8 mėnesių atnaujinu šitą pasikeitimų sąrašą!</strong> Iš esmės nėra taip, kad nevyksta tinklalapio pokyčių, tiesiog apie juos rašyti yra papildomas darbelis, nu bet nieko.</p>
            <p><strong>Nuo vasario mėn...</strong></p>
            <h4> Pagrindiniai atnaujinimai:</h4>
            <ul>
                <li>Padaliniai savo <a href="http://vusa.lt/admin">pagrindiniuose admin puslapiuose</a> matys galimybę įjungti EN režimą, kuris daro tai, kas ten ir parašyta. :) O jei trumpai - galite įkelti EN informaciją daug sklandžiau, negu tą buvo galima daryti anksčiau.
                </li>
                <li>Bendrai daugiau lokalizacijos anglų kalba, pvz.: apatinėje VU SA juostoje</li>
                <li>Pridėti FB Messenger burbuliukai, beveik visur jie yra ir specifiniai padaliniams</li>
            </ul>
            
            <p>
                Buvo ir daugiau visokių pataisymų, bet jau tingiu rašyti. :D Nuo šiol pradėsiu gal šiek tiek detaliau.
            </p>


            <hr style="border-top: 1px solid #111">
            <h3> <strong>2021-02-17 </strong></h3>
            <p>Taigi, toliau tęsiasi sistemos kuopimas ir vidinis naujinimas. Papildomų funkcijų šįkart, kaip ir praeitąkart, nėra daug, bet kai kurie dalykai turbūt šiek tiek nustebins, atsidarius. :) Bet pokyčiai daugiausiai stilistiniai, puslapio funkcijos realiai visiškai nesikeitė.</p>
            <h4> Pagrindiniai atnaujinimai:</h4>
            <ul>
                <li> Atnaujinta vidinė administravimo platforma. Labiausiai pastebėsite per tai, kad pasikeitė vidinis stilius, spalvos ir pan. Daugiausiai buvo atnaujinta dėl techninių priežasčių, kurias būtų sunku paprastai paaiškinti. :D <strong> Reikia pastebėti, kad yra likę visokių stilistinių bug'ų, tai nebijokit, bus sutvarkyta. Funkciškai viskas veikia (ir dar šiek tiek netgi geriau).</strong></li>
                <li> Atnaujinta failų tvarkytuvė. Turi savų pliusų (saugumas, geresnė integracija) ir minusų, bet ją buvo būtina atnaujinti.</li>
                <li> Padalinių puslapiuose: jeigu esate koordinatorių, kuratorių ar studentų atstovų puslapyje, nueikite į Padaliniai->[Kitas padalinys] ir bus galima iškart atsidaryti kito padalinio atitinkamą informaciją. Dėl tos pačios priežasties į kiekvieną padalinio puslapį įdėtas grįžimo mygtukas į pradinį padalinio puslapį (stilius laikinas).</li>
                <li>Pereita prie PHP 8. (dėl ko ir kilo nemažai problemų <i>in the first place</i>).</li>
            </ul>
            <h4>Kiti smulkūs pataisymai</h4>
            <ul>
                <li>Keliant naujienas, mėnesis kai kuriais atvejais nebenusistatys į sausį, o laikas nenusimuš į 0 ar 1, kai to nereikia.</li>
                <li>Į naujienų nuorodas nebeįsidės šauktukai (kai jie imami iš pavadinimo).</li>
                <li>Prisiloginus, bus atidarytas šis puslapis. Gal labai netrikdys. :)</li>
                <li>Daug visokių kitų pataisymų, kurių didžiąją dalį jau ir užmiršau tiesą sakant. Ačiū, kad pastebit ir pasakot. ❤️ </li>
            </ul>

            <hr style="border-top: 1px solid #111">
            <h3> <strong>2021-01-26 </strong></h3>
            <h4> Pagrindiniai atnaujinimai:</h4>
            <ul>
                <li> <strong>Visiškai atnaujinti puslapio griaučiai.</strong> Svarbu, nes nuo to priklausys ir ateities
                    atnaujinimai,
                    tai bus daugiau galimybių tam.</li>
                <li> <strong>Pridėtas VU SA ŠA padalinys!</strong></li>
                <li> Išjungtas privatumo politikos patvirtinimas administraciniuose puslapiuose ir kai kurių klaidų puslapiuose.</li>
                <li> Atnaujinta /admin navigacinė juosta. Intuityviau sudėliotos skiltys ir pašalintos tos, kurių nereikia.
                </li>
                <li> Atnaujinta VU SA puslapio ikona ir labiau pritaikyta telefonam. (Nežinau, ar geriau, bet man atrodo
                    geriau) <br> <br> Anksčiau: <br> <br>
                    <img src={{ asset('images/changelog/D70D6988F0604A6F2464BF196A6672A82F8EF762.png') }}
                        class="img-fluid" style="max-width: 40%"> <br> Dabar: <br> <br> <img
                        style="max-width: 40%" class="img-fluid" 
                        src={{ asset('images/changelog/CDDDC5491F382C0F2FDBE7D551E6AD6D1CEC35AF.png') }}> <br> <br> Taip pat
                    telefonuose nuo šiol irgi matomos puslapio ikonos. <br> <br> <img
                        src={{ asset('images/changelog/1AB3C97C4F58757ACDA15E25648FA0E0C43DE951.png') }}
                        class="img-fluid" style="max-width: 40%"> <br>
                </li>
                <li>Veikia nuorodos į tam tikras žymes padalinių naujienose. Anksčiau tokią paspaudus, išmesdavo klaidos
                    pranešimą. <strong>Ačiū Lijanai, kad pastebėjo ir pranešė!</strong></li>
                <li> Privatumo sąlygų patvirtinimo apatinė juosta nebepradings už apatinės puslapio juostos.</li>
                <li> Apatinė juosta nuslinks į pačią apačią, net jeigu neužtenks turinio ekrane. <br> <br> Anksčiau: <br>
                    <br>
                    <img src={{ asset('images/changelog/A79127CDDFD732B6D5C0B4753FDC8E541293F210.png') }}
                        class="img-fluid" style="max-width: 40%"> <br> <br> Dabar: <br> <br> <img
                        src={{ asset('images/changelog/370C93F1AB8363DB2EA7C56ADD4F79E8E132384B.png') }}
                        style="max-width: 40%" class="img-fluid">
                </li>
                <li> Paleistas <a href="admin/atnaujinimai"> šis vidinis atnaujinimų</a> puslapiukas. :)</li>
            </ul>
            <br>
            <h4>Labiau techniniai, bet irgi svarbūs atnaujinimai:</h4>
            <ul>
                <li> Optimizuota, kurie <code>CSS</code> ir <code>JS</code> paketai turi būti užkraunami. Todėl puslapiai turėtų būti užkraunami šieek tiek greičiau. <i>Ateityje bus
                    naudojamos jų tvarkyklės.</i></li>
                <li>Pašalintas static.vusa.lt. Anksčiau buvo naudotas failam, pav, bet to nebereikia (serveris
                    tiesiogiai užkrauna failus). Todėl nuo šiol visų failų sukuriamos nuorodos bus vusa.lt/…, tačiau dar
                    matysite
                    static.vusa.lt nuorodų jau sukurtose naujienose ar puslapiuose. Tas bus sutvarkyta <i> sooner or
                        later...</i>
                </li>
                <li> „Puslapis nerastas“ puslapis (tik) nuo šiol siunčia HTTP 404 atsaką. Jei paprastai paaiškinant,
                    naršyklės nuo
                    šiol supranta, kad visi „Puslapis nerastas“ puslapiai iš tikrųjų yra tokie. <i> Čia palikimas nuo
                        anksčiau,
                        šiaip tikrai reikėjo seniai šitą padaryti. :)</i> </li>
            </ul>
        </section>
    @endsection
