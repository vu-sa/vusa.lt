<template>
  <Sveikinimai>
    <h1 class="text-3xl text-center col-span-4 md:col-span-3 mb-8 -order-2">
      Sveikinimai
    </h1>
    <div
      class="col-span-4 order-1 md:-order-1 md:col-span-3 col-start-1 prose max-w-full md:max-w-prose"
    >
      <NCarousel
        draggable
        dot-placement="top"
        :show-arrow="true"
        :space-between="20"
        :show-dots="false"
        :current-index="carouselIndex"
        @update:current-index="handleCarouselIndex"
      >
        <NCarouselItem>
          <img
            class="carousel-img"
            style="object-position: 0% 20%"
            src="/images/ataskaita2022/sveikinimai/MP portretas.jpg"
          />
          <div class="bg-white p-6 text-sm">
            <p>Mieli Vilniaus universiteto studentai ir studentės,</p>

            <p>
              praėjusieji metai ir šių metų pradžia vėl atnešė didelių, anksčiau nepatirtų
              iššūkių. Jau trejus metus gyvename visuotinio neapibrėžtumo sąlygomis ir
              mokomės veikti geriausiai, kaip tik galime, – ieškome sprendimų, bandydami
              įžvelgti kiekvienos situacijos teikiamas galimybes ir sparčiai reaguodami į
              pokyčius, o kartu ir nepamirštame kelti jau ilgą laiką brendusių klausimų ir
              ieškoti konstruktyvių sprendimų.
            </p>

            <p>
              Labai džiugina ryžtinga Vilniaus universiteto studentija – susidūrus su
              sunkumais, jūs nelikote nuošalyje, netapote pasyviais stebėtojais, bet patys
              nuolat ieškote būdų ir siūlote, kaip jūsų bendruomenė gali prisidėti prie
              aktualių problemų sprendimų.
            </p>

            <p>
              Sklandžiai kovodami su iššūkiais, tuo pat metu nuosekliai keliate
              studentijai bei visai visuomenei aktualius klausimus, susijusius su studijų
              kokybės užtikrinimu, dėmesiu studentų balso svarumui studijų procese,
              aukštojo mokslo prieinamumo galimybėmis per socialinės dimensijos prizmę.
              Labai džiugu, kad, kalbėdami apie studijų kokybės gerinimą, patys plėtojate
              akademinio sąžiningumo temą ir veikiate, siekdami jį užtikrinti, pavyzdžiui,
              sukūrėte ir ne vienus metus įgyvendinate programą „Sąžiningai“. Skatinate
              sąmoningumą ir raginate studentus savarankiškai valdyti ir individualizuoti
              savo studijų planus. Labai sveikintina jūsų iniciatyva kalbėti apie neįgalių
              studentų integraciją iš jų pačių perspektyvos. Viena vertus, reikia
              tobulinti biurokratinę sistemą, kita vertus, ne ką mažiau reikšmingas ir
              jūsų akcentuojamas bendruomenės gebėjimas priimti įvairių poreikių turinčius
              studentus. Taip pat dėkoju, kad aktyviai prisidėjote prie socialinės
              dimensijos koncepcijos rengimo ir tobulinimo, analizuojate, kaip įtraukti
              studentus į mokslinius projektus, sprendžiate, ką būtų galima padaryti, kad
              doktorantūros studijos pateisintų jaunųjų mokslininkų lūkesčius, o baigę
              doktorantūros studijas jie galėtų stiprinti Lietuvos mokslą ir sėkmingai
              įsilieti į Lietuvos mokslininkų bendruomenės gretas.
            </p>

            <p>
              Dėkoju už jūsų idėjas ir konstruktyvų bendradarbiavimą ieškant geriausių
              sprendimų, siekiant, kad studijos taptų prieinamos visiems norintiems eiti
              akademiniu keliu ir įkvepiančios niekada nenustoti mokytis ir smalsiai
              žvelgti į pasaulį.
            </p>

            <p>Lietuvos Respublikos Ministrė Pirmininkė</p>
            <strong>Ingrida Šimonytė</strong>
          </div>
        </NCarouselItem>
        <NCarouselItem>
          <img
            class="carousel-img"
            style="object-position: 0% 20%"
            src="/images/ataskaita2022/sveikinimai/JurgitaSiugzdiniene.jpg"
          />
          <div class="bg-white p-6 text-sm">
            <p>Mieli studentai,</p>

            <p>
              džiugu, kad Vilniaus universiteto Studentų atstovybė pasižymi neblėstančiu
              aktyvumu ir pilietiškumu, gebėjimu veikti ir vienyti. Šiuo neramiu laiku
              įnešėte didžiulį indėlį, siekdami suteikti kuo daugiau aiškumo savo
              bendruomenei ir ją padrąsinti. Tikiu, kad į savo gretas mielai priimsite ir
              tuos, kurie šiuo metu išgyvena daugiausiai – darykime viską, kad
              ukrainiečiai broliškoje šalyje jaustųsi laukiami, savi.
            </p>

            <p>
              Linkiu jums ir toliau tęsti prasmingus darbus ir visapusiškai tobulėti,
              ugdyti ne tik gebėjimą prisitaikyti prie vis naujai kylančių iššūkių, bet ir
              užsispyrimą atrasti veiksmingus jų sprendimo būdus. Šiuolaikinis jaunas
              žmogus ir pasižymi tuo, jog yra atviras, ryžtingas bei veržlus, nepaisant jį
              kamuojančio nežinomybės jausmo.
            </p>

            <p>
              Nuoširdžiai sveikinu jus su reikšmingais praėjusių metų pasiekimais ir
              tikiuosi, kad nenustosite rodyti jaunatviškos drąsos ir solidarumo su
              šiandienos kovotojais už laisvę.
            </p>

            <p>Švietimo, mokslo ir sporto ministrė</p>
            <strong>Jurgita Šiugždinienė</strong>
          </div>
        </NCarouselItem>
        <NCarouselItem>
          <img
            class="carousel-img"
            style="object-position: 0% 20%"
            src="/images/ataskaita2022/sveikinimai/Žukauskas.XIII-020-S20210121-O Posaškovos-0234.JPG"
          />
          <div class="bg-white p-6 text-sm">
            <p>Mieli studentai,</p>

            <p>
              sveikinu su dar vienais sėkmingos veiklos metais! Jūsų aktyvumas ieškant
              geriausių sprendimų virsta energija, skatinančia judėti į priekį, žvelgti
              giliau, ieškoti tobulesnių permainų formulių. Pastarieji metai paženklinti
              neeilinių iššūkių, tačiau žaviuosi jūsų susitelkimu, drąsiomis ir
              jaunatviškomis iniciatyvomis, kurios įkvepia ir nepalieka abejingų. Tęskite
              pradėtus darbus, drąsiai ir atsakingai imkitės naujų! Saugokite ir mylėkite
              brangią Lietuvą, didžiuokitės Tėvynės praeitimi ir kurkite klestinčią
              ateitį, žengdami švietimo ir mokslo pažangos keliu! Linkiu jums ryžto ir
              energijos siekiant lyderystės ir profesionalumo savo srityse bei kilnių
              siekių išsipildymo!
            </p>

            <strong>Prof. Artūras Žukauskas</strong>
            <p>LR Seimo Švietimo ir mokslo komiteto pirmininkas</p>
          </div>
        </NCarouselItem>
        <NCarouselItem>
          <img
            class="carousel-img"
            src="/images/ataskaita2022/sveikinimai/rektorius.jpg"
          />
          <div class="bg-white p-6 text-sm">
            <p>Mieli Studentai,</p>

            <p>
              pastarieji metai buvo sudėtingi ir kupini globalių iššūkių, paliečiančių
              visų mūsų gyvenimus. Tikiu, kad jie dar labiau sustiprino Universiteto
              vaidmenį ir atskleidė jo esmę. Būtent Universitete gimsta ir yra vystomos
              idėjos, kuriančios, palaikančios valstybę ir visuomenę. Universitetas
              išlaisvina mąstyti savarankiškai ir veikti visuomeniškai. Universitetas
              suteikia instrumentus ir sąlygas įprasminti save. Ir net tampa prieglobsčiu
              nuo negandų ir nelaimių.
            </p>

            <p>
              Noriu nuoširdžiai pasidžiaugti, kad Vilniaus universiteto studentai tiek
              pandemijos, tiek karo Ukrainoje akivaizdoje demonstravo ne tik solidarumą,
              bet ir tapo tikrais visuomenės lyderiais. Jūsų dėka Universitetas
              deklaruojamas vertybes paverčia realiais darbais. Studentai tapo
              savanoriais, rėmėjais, mentoriais, protestuotojais, paramos rinkėjais,
              dalintojais ir tuo pat metu išliko aktyviais ir reikšmingais akademinės
              bendruomenės nariais.
            </p>

            <p>
              Sveikinu Vilniaus universiteto Studentų atstovybę, kuri reiškiasi ne tik
              kaip aktyvi bendruomenės dalis, bet ir kaip naujų idėjų, akademinio ir
              socialinio aktyvumo bei pagarbaus dialogo šaltinis. Tikiu, kad kasmetinis
              darbų įsivertinimas bus proga ne tik pasidžiaugti tuo, kas atlikta, bet ir
              įvertinti tai, ką dar galite nuveikti Universiteto, visuomenės, valstybės, o
              tai reiškia ir savo labui.
            </p>

            <p>
              Svarbu, kad studentų balsas būtų dar labiau girdimas ne tik Universitete,
              bet ir visuomenėje. Tam tikras tiesas banalu kartoti, bet jas vis
              pakartojame todėl, kad esame įsitikinę jų teisingumu. Tad pasikartosiu ir aš
              – Jūs esate ateitis. Mūsų atsakomybė Jums padėti kilti aukštyn, Jūsų –
              surasti motyvacijos ir drąsos ambicijų išsipildymui.
            </p>

            <p>
              Linkiu toliau sėkmingai atstovauti Universitetą ir jo studentus, kurti
              idėjas ir rengti siūlymus, didinančius bendrą mūsų visų gėrį.
            </p>

            <p>Vilniaus universiteto rektorius</p>
            <strong>prof. Rimvydas Petrauskas</strong>
          </div>
        </NCarouselItem>
        <NCarouselItem>
          <img
            class="carousel-img"
            src="/images/ataskaita2022/sveikinimai/senato pirmininke.jpg"
          />
          <div class="bg-white p-6 text-sm">
            <p>Brangūs Vilniaus universiteto studentai,</p>

            <p>
              kai pirmieji Europos universitetai, įkurti Italijoje, Bolonijoje ir
              Padujoje, artėja prie tūkstantmečio ribos, o kai kurie naujieji skaičiuoja
              vos savo pirmuosius dešimtmečius, mums Vilniaus universiteto istorija
              dovanoja garbingą vietą pačiame Europos raidos centre. Jau netrukus minėsime
              ir 445 mūsų Alma Mater jubiliejų, taigi jau penktą šimtmetį Vilniaus
              universitetas, brandindamas kartą po kartos, kuria Lietuvos istoriją.
            </p>

            <p>
              Mūsų universitetui istorijos deivė Klio yra lėmusi radikalias kaitas ir
              patirtis, kurias turime nuolat apmąstyti ir įvertinti, siekdami kūrybiškai
              plėtoti savo intelektualinę tapatybę. 2021–2022 mokslo metai buvo pažymėti
              pandemijos, prasidėjusių karo veiksmų Ukrainoje, kurie paskatino budrumą,
              susitelkimą ir ryžtą. Kaip pastebėjo senovės graikų mąstytojas Tukididas –
              istorija yra tikriausia praktinės filosofijos forma.
            </p>

            <p>
              Vilniaus universiteto Senatas, suformuotas 2018-aisiais, 2022 metų pavasario
              semestrą baigia savo kadenciją. Šis Senatas buvo jau antrasis, veikęs pagal
              naująjį VU Statutą; jam teko įtvirtinti atliktas reformas, prisiimti
              atsakomybę už akademinę veiklą ekstremaliomis aplinkybėmis. Dėkodama Senate,
              kitose Vilniaus Alma Mater akademinėse ir organizacinėse srityse
              veikiantiems studentams, noriu pabrėžti, kad jų atsakomybė, jautrumas ir
              dėmesingumas sprendžiant visai bendruomenei aktualius klausimus yra
              reikšminga mūsų Universiteto savivaldos dalis, kurią būtina plėtoti ir
              puoselėti. Tad kiekvienam Vilniaus universiteto studentui noriu palinkėti
              visą gyvenimą išlikti dvasią keliančios bendruomenės nariu, įgyvendinti kuo
              daugiau savo tikslų ir su viltimi bei meile puoselėti visiems mums brangaus
              universiteto, kuris buvo kuriamas net ir sunkiausiais laikais prieš mus bei
              liks ir po mūsų, veidą.
            </p>

            <p>VU Senato pirmininkė</p>
            <strong>Prof. Dainora Pociūtė-Abukevičienė</strong>
          </div>
        </NCarouselItem>
        <NCarouselItem>
          <img
            class="carousel-img"
            style="object-position: 0% 20%"
            src="/images/ataskaita2022/sveikinimai/Radzeviciene.5M5A1668r_3.jpg"
          />
          <div class="bg-white p-6 text-sm">
            <p>Gerbiami jaunieji kolegos ir kolegės,</p>

            <p>
              prabėgo dar vieni įtampos ir iššūkių kupini metai. Drįstu pasakyti – mokslo
              ir mokymosi metai ne tik mūsų Alma mater studentų bendruomenei, bet ir visai
              visuomenei. Kartu transformavome darbo ir mokymosi formas, išgryninome savo
              vertybes ir socialinę poziciją, drauge kūrėme šiuolaikinį ir įtraukų
              Vilniaus universitetą. Džiugu matyti, kad studentija buvo aktyvus ir brandus
              visų šių virsmų dalyvis ir partneris.
            </p>

            <p>
              Visos VU Tarybos vardu dėkoju jums už partnerystę, iniciatyvą ir
              savanorystę. Su visa savo jaunatvine energija kurkite ir toliau savo
              universitetą – tarpusavio pagarba paremtą tarptautinę bendruomenę, kur
              vyksta dialogas tarp skirtingų kartų, kur dera mokslas ir studijos su
              prasmingu laisvalaikiu ir socialine atsakomybe bei savanoryste.
            </p>

            <strong>Eglė Radzevičienė</strong>
            <p>VU Tarybos pirmininkė</p>
          </div>
        </NCarouselItem>

        <NCarouselItem>
          <img
            class="carousel-img-2"
            src="/images/ataskaita2022/sveikinimai/Kriviene.biblioteka.jpg"
          />
          <div class="bg-white p-6 text-sm">
            <p>Mielos studentės, mieli studentai,</p>

            <p>
              Jūs, pati didžiausia ir pati dinamiškiausia Vilniaus universiteto
              bendruomenės dalis, esate pagrindiniai mūsų Bibliotekos lankytojai (-os).
              Čia mokotės, dirbate, užmezgate pažintis, bendraujate. Nepaprastai džiugina
              Jūsų domėjimasis tuo, ką dirba ir kuo gyvena Biblioteka. Mes, Bibliotekos
              darbuotojai, esame nuoširdžiai dėkingi (-os) už atvirumu grįstą
              bendradarbiavimą ir mūsų organizacijas siejantį konstruktyvų dialogą.
            </p>

            <p>
              VU SA atstovų (-ių) aktyvus įsitraukimas rengiant 2022–2025 metų Bibliotekos
              strateginį veiklos planą padėjo mums išgeneruoti sritis, į kurias
              koncentruosimės artimiausiu metu tobulinant informacinę studijų aplinką. Tai
              – atliepimas individualiems informaciniams vartotojų poreikiams,
              Bibliotekos, kaip informacinio / akademinio raštingumo centro, o taip pat
              kaip Universiteto bendruomenę jungiančios erdvės, veiklų stiprinimas ir kt.
              VU SA prezidentės mintys, išsakytos vertybių klausimui skirtoje diskusijoje,
              stipriai prisidėjo randant bendrą sprendimą, kokiu keliu eidami galėtume
              sinchronizuoti organizacijos (šiuo atveju Universiteto) ir jos struktūrinio
              padalinio (šiuo atveju Bibliotekos) vertybines nuostatas.
            </p>
            <p>
              Biblioteka dėkinga Jums už skatinimą tobulėti, už tai, kad ne tik vertinate
              jos darbą, bet ir siūlote, ką ir kaip daryti, kad informacinės paslaugos ir
              informacinė aplinka kuo labiau atlieptų paslaugų vartotojų lūkesčius. VU SA
              ataskaitiniu laikotarpiu Biblioteka, naudodamasi informacijos įstaigų
              pasaulyje pripažintu ir plačiai naudojamu vartotojų nuomonės tyrimo įrankiu
              <em>LibQual</em>, pirmą kartą atliko išplėstinę vartotojų apklausą. Tiriant
              vartotojų nuomonę nustebino ir taip pat pradžiugino didžiulis pildžiusiųjų
              anketas komentarų kiekis, patvirtinantis faktą, kad Biblioteka Jums rūpi.
              Nuoširdžiai dėkojame už pagalbą viešinant <em>LibQual</em> apklausą,
              aktyvumą išsakant savo nuomonę ir suteiktą grįžtamąjį ryšį.
            </p>

            <p>
              Jauni žmonės visada yra naujovių generavimo ir jų taikymo avangarde, todėl
              VU SA atstovo įsijungimas į
              <em>Atvirojo mokslo politikos gairių</em> rengimo darbo grupę buvo ir
              lauktas, ir labai pasiteisinęs: reikšmingai prisidėta prie šio dokumento
              rengimo ir atvirųjų mokymo(si) išteklių temos atsiradimo jame.
            </p>

            <p>
              Labai greitai įsiliesite į Lietuvos ar užsienio šalių verslo įmonių,
              valstybinių institucijų ar akademinę veiklas. Neabejoju, kad VU SA įgyta
              vadybinė, pilietinės ir socialinės atsakomybės patirtis, o taip pat
              užsimezgę ryšiai padės jums profesinės karjeros ir asmeninio gyvenimo
              kelyje. Linkiu niekada neprarasti ryšio su savo Alma Mater. Visada esate ir
              būsite laukiami VU bibliotekoje!
            </p>

            <strong>Irena Krivienė</strong>
            <p>VU bibliotekos generalinė direktorė</p>
          </div>
        </NCarouselItem>
        <NCarouselItem>
          <img
            class="carousel-img-2"
            style="object-position: 0% 20%"
            src="/images/ataskaita2022/sveikinimai/dr. Loreta Tauginienė.MART1397B.jpg"
          />
          <div class="bg-white p-6 text-sm">
            <p>Mieli studentai, mielos studentės,</p>

            <p>
              atkakliai, aktyviai ir nuosekliai skatinate laikytis akademinės etikos
              principų ir taip prisidedate prie studijų kokybės. Jūs atnešate naujoves,
              kurios visapusiškai keičia akademinės etikos kultūrą. Akademinė etika ypač
              puoselėjama tiesiogiai šviečiant ir kalbantis su akademine bendruomene.
              Kvietimas studentams (-ėms) aktyviau domėtis specialiu kursu „Akademinė
              etika“, metodinės medžiagos parengimas, atvirumas konsultacijoms ir kitos
              iniciatyvos – keli pavyzdžiai, kai Jūsų įsitraukimas yra reikšmingas.
              Svarbiausia, kūrybiškai ir drąsiai žengti į priekį – tik taip tampa įmanomi
              Lietuvos studijų ir mokslo sistemos pokyčiai.
            </p>

            <p>
              Vertinu Jūsų indėlį ir kviečiu kartu siekti tarptautinių akademinės etikos
              standartų.
            </p>

            <p>
              <strong>V</strong>ieningai <strong>U</strong>ž <strong>S</strong>tudentų
              <strong>A</strong>teitį!
            </p>

            <p>Akademinės etikos ir procedūrų kontrolierė</p>
            <strong>dr. Loreta Tauginienė</strong>
          </div>
        </NCarouselItem>
        <NCarouselItem>
          <img
            class="carousel-img-2"
            style="object-position: 0% 10%"
            src="/images/ataskaita2022/sveikinimai/Simasius.jpeg"
          />
          <div class="bg-white p-6 text-sm">
            <p>Mieli studentai,</p>

            <p>
              pirmieji Jūsų savarankiško gyvenimo žingsniai ir vėl išbandomi sudėtingais
              iššūkiais. Koronaviruso pandemijos apribojimus pakeitė akistata su
              sukrečiančia Rusijos agresija.
            </p>
            <p>
              Ir čia visuomenei ypač reikia Jūsų pozityvumo, drąsos ir svarbiausia – tikro
              įsitraukimo. Dalinkitės informacija, aiškinkite abejojantiems, kurkite
              valstybę ir ateitį, kurioje patys norite gyventi.
            </p>
            <p>
              O Vilnius visada bus jūsų – įsitvirtinusių po studijų ar nusprendusių po
              studijų apsidairyti svetur. Lauksime sugrįžtant.
            </p>

            <p>Vilniaus meras</p>
            <strong>Remigijus Šimašius</strong>
          </div>
        </NCarouselItem>
        <NCarouselItem>
          <img
            class="carousel-img-2"
            src="/images/ataskaita2022/sveikinimai/arminas_2021_profilis.jpg"
          />
          <div class="bg-white p-6 text-sm">
            <p>VU SA,</p>

            <p>
              sveikinu su rezultatų kupinais metais! VU SA yra matoma ir girdima
              nacionaliniu lygmeniu – aukštojo mokslo finansavimas, studentų padėties
              gerinimas, socialinės dimensijos klausimai – jokie sprendimai nėra priimami
              be Jūsų. Esate kviečiami kaip ekspertai skaityti pranešimų, aktyviai
              dalyvaujate Arqus universitetų aljanso veiklose, esate pagrindinė jėga
              stumianti Universitetą pereiti prie vakarietiškesnio studijų organizavimo
              modelio, leidžiančio studentams savarankiškai rinktis bent 60 kreditų.
            </p>

            <p>
              Smagu matyti, kad sukuriate geras sąlygas studentams veikti ir PKP spektras
              plečiasi, sveikintina, kad pradėjote diegti refleksijos kultūrą
              organizacijoje (nors tam virsti realybe dar prireiks laiko), integravote
              naują padalinį iš Šiaulių. Ir visa tai Jums pavyko pasiekti dar
              nesibaigusios pandemijos ir prieš kelis mėnesius prasidėjusio karo
              kontekste. Dar daugiau – sugebėjote greitai reaguoti į situaciją ir
              pasiūlyti pagalbą tiems žmonėms, kuriems jos labiausiai reikia.
            </p>

            <p>
              Praėję metai buvo sunkūs. Šie metai buvo sunkūs. Nebus lengvesni ir
              ateinantys metai. Turiu 5 patarimus, kurie būtini, siekiant, kad VU SA ir
              toliau augtų kaip organizacija bei išspręstų dar daugiau studentų problemų:
            </p>
            <p>
              atvirumas, atsakomybė, veiklos kokybė, vieningumas ir tobulėjimas. Never
              forget!
            </p>

            <p>Vieningai,</p>
            <strong>Arminas Varanauskas</strong>
          </div>
        </NCarouselItem>
        <NCarouselItem>
          <img
            class="carousel-img"
            src="/images/ataskaita2022/sveikinimai/Romas-LMT-9.jpeg"
          />
          <div class="bg-white p-6 text-sm">
            <p>Mieli studentai,</p>

            <p>
              sveikinu ir dėkoju už jūsų kūrybiškumą, pilietinį aktyvumą, siekį įgyti
              platų universitetinį išsilavinimą. Ypač džiugina jūsų pirmieji žingsniai
              kuriant naujas mokslo žinias, kurios galiausiai prisidės prie didėjančio
              Valstybės konkurencingumo. Naujausios mokslo žinios yra pagrindas ir
              kokybiškoms universitetinėms studijoms.
            </p>

            <p>
              Šiais metais Valstybė pastebimai padidino investicijas į mokslinius tyrimus
              ir eksperimentinę (socialinę, kultūrinę) plėtrą. Dalis šių investicijų yra
              nukreipta į Lietuvos mokslo tarybos finansuojamus studentų tyrimų projektus,
              kurie vykdomi tiek semestro, tiek ir vasaros metu. Tai – paskata studentams
              įsitraukti į aktyvią mokslinę veiklą ir kelti mokslinę kompetenciją. Džiugu,
              kad Vilniaus universiteto studentai vieni aktyviausių šioje veikloje.
            </p>

            <p>
              Džiugina jūsų jaunatviškas ryžtas, aktyvumas, nevaržomas maksimalizmas.
              Linkiu niekada neprarasti šių savybių, nes jūsų rankose yra šalies ateitis.
              Sėkmės ir gero ūpo įgyvendinant savo sumanymus!
            </p>

            <p><em>Gaudeamus igitur, iuvenes dum sumus!</em></p>

            <p>Lietuvos mokslo tarybos pirmininkas</p>
            <strong>Prof. Romas Baronas </strong>
          </div>
        </NCarouselItem>
      </NCarousel>
    </div>
    <div
      class="flex flex-wrap justify-center md:justify-start md:flex-col mb-8 gap-2 col-span-4 md:col-span-1 items-start"
    >
      <NButton text="" @click="carouselIndex = 0">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          :class="{ grayscale: carouselIndex !== 0 }"
          src="/images/ataskaita2022/sveikinimai/MP portretas.jpg"
        />
      </NButton>
      <NButton text="" @click="carouselIndex = 1">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          :class="{ grayscale: carouselIndex !== 1 }"
          src="/images/ataskaita2022/sveikinimai/JurgitaSiugzdiniene.jpg"
        />
      </NButton>
      <NButton text="" @click="carouselIndex = 2">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          :class="{ grayscale: carouselIndex !== 2 }"
          src="/images/ataskaita2022/sveikinimai/Žukauskas.XIII-020-S20210121-O Posaškovos-0234.JPG"
        />
      </NButton>
      <NButton text="" @click="carouselIndex = 3">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          :class="{ grayscale: carouselIndex !== 3 }"
          src="/images/ataskaita2022/sveikinimai/rektorius.jpg"
        />
      </NButton>
      <NButton text="" @click="carouselIndex = 4">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          :class="{ grayscale: carouselIndex !== 4 }"
          src="/images/ataskaita2022/sveikinimai/senato pirmininke.jpg"
        />
      </NButton>
      <NButton text="" @click="carouselIndex = 5">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          :class="{ grayscale: carouselIndex !== 5 }"
          src="/images/ataskaita2022/sveikinimai/Radzeviciene.5M5A1668r_3.jpg"
        />
      </NButton>
      <NButton text="" @click="carouselIndex = 6">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          :class="{ grayscale: carouselIndex !== 6 }"
          src="/images/ataskaita2022/sveikinimai/Kriviene.biblioteka.jpg"
        />
      </NButton>
      <NButton text="" @click="carouselIndex = 7">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          :class="{ grayscale: carouselIndex !== 7 }"
          src="/images/ataskaita2022/sveikinimai/dr. Loreta Tauginienė.MART1397B.jpg"
        />
      </NButton>
      <NButton text="" @click="carouselIndex = 8">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          :class="{ grayscale: carouselIndex !== 8 }"
          src="/images/ataskaita2022/sveikinimai/Simasius.jpeg"
        />
      </NButton>
      <NButton text="" @click="carouselIndex = 9">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          :class="{ grayscale: carouselIndex !== 9 }"
          src="/images/ataskaita2022/sveikinimai/arminas_2021_profilis.jpg"
        />
      </NButton>
      <NButton text="" @click="carouselIndex = 10">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          :class="{ grayscale: carouselIndex !== 10 }"
          src="/images/ataskaita2022/sveikinimai/Romas-LMT-9.jpeg"
        />
      </NButton>
    </div>
    <div class="col-span-4 mb-12 order-3">
      <Link
        class="text-lg duration-200 hover:text-red-600 font-bold"
        :href="
          route('main.ataskaita2022', { lang: $page.props.locale, permalink: 'vu-sa' })
        "
        >Apie VU SA >></Link
      >
    </div>
  </Sveikinimai>
</template>

<script setup>
import Sveikinimai from "../1-Sveikinimai.vue";
import { NCarousel, NCarouselItem, NButton } from "naive-ui";
import { ref } from "vue";
import { Link } from "@inertiajs/inertia-vue3";

const carouselIndex = ref(0);

const handleCarouselIndex = (index) => {
  carouselIndex.value = index;
};
</script>

<style scoped>
.carousel-img {
  width: 100%;
  height: 280px;
  object-fit: cover;
  border-radius: 1em 1em 0 0;
  margin-top: 0;
  margin-bottom: 0;
}

.carousel-img-2 {
  width: 100%;
  height: 365px;
  object-fit: cover;
  border-radius: 1em 1em 0 0;
  margin-top: 0;
  margin-bottom: 0;
}
</style>
