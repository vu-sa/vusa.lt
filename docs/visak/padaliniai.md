---
doc_status: reviewed
title: Padaliniai
last_reviewed: 2026-11-11
tests:
  - tests/Feature/Api/Admin/RecordActivityStatusTest.php
  - tests/Feature/Admin/Dashboard/AtstovavimasDashboardTest.php
  - tests/Feature/Admin/Core/AtstovavimasApiTest.php
  - tests/Feature/Api/Admin/RepStatisticsTest.php
  - tests/Feature/Admin/Core/AtstovavimasSettingsTest.php
  - tests/Feature/Institutions/InstitutionScopeTest.php
  - tests/Feature/Admin/Core/InstitutionStatusSnapshotTest.php
  - tests/Unit/InstitutionActivityStatusServiceTest.php
  - tests/Unit/Enums/InstitutionActivityStatusTest.php
  - resources/js/Pages/Admin/Dashboard/__tests__/ShowAtstovavimasPadaliniai.component.test.ts
  - resources/js/Pages/Admin/Dashboard/Components/__tests__/TenantScopeSelector.component.test.ts
  - resources/js/Pages/Admin/Dashboard/Components/__tests__/GanttFilterDropdown.component.test.ts
  - resources/js/Pages/Admin/Dashboard/Components/__tests__/InstitutionStatusTrendChart.component.test.ts
  - resources/js/Pages/Admin/Dashboard/Composables/__tests__/statusFlow.test.ts
  - resources/js/Pages/Admin/Dashboard/Composables/__tests__/useTimelineFilters.test.ts
  - resources/js/Pages/Admin/Dashboard/Composables/__tests__/useTenantMeetings.test.ts
  - resources/js/Components/Graphs/composables/__tests__/useGanttSettings.test.ts
  - resources/js/Pages/Admin/Dashboard/Components/__tests__/TenantTimelineSection.component.test.ts
  - resources/js/Components/Graphs/__tests__/MeetingsGantt.component.test.ts
  - resources/js/Components/Graphs/composables/__tests__/useGanttLayout.test.ts
  - resources/js/Components/Graphs/composables/__tests__/useGanttFiltering.test.ts
  - resources/js/Components/Graphs/composables/__tests__/useGanttLabels.test.ts
  - resources/js/Components/Graphs/__tests__/MeetingsGanttToolbar.component.test.ts
  - resources/js/Components/Graphs/composables/__tests__/useGanttInteractions.test.ts
  - resources/js/Pages/Admin/Dashboard/Composables/__tests__/statusTrend.test.ts
  - resources/js/Components/Home/__tests__/HomeSections.component.test.ts
  - tests/Browser/VisakOverviewPagesTest.php
---

# Padaliniai

**ViSAK → Padaliniai** (`/mano/dashboard/atstovavimas/padaliniai`) yra viso padalinio vaizdas:
kurioms institucijoms reikia dėmesio, kaip keičiasi jų būklė ir kada vyksta posėdžiai. Atstovų
aktyvumas ir atstovavimo rodikliai yra [Atstovų](/visak/atstovai) puslapyje. Puslapį sudaro dvi dalys:

- **Rodikliai** – skaičiai, dėmesio sąrašas ir būklės pokyčiai. Jie rodomi tik
  tų padalinių, kurių institucijas naudotojas turi teisę matyti.
- **Posėdžių laiko juosta** – ją mato **visi**, bet kiekvienas tik tiek, kiek jam leidžiama.

<DocScreenshot name="visak-padaliniai" alt="Padalinių apžvalga: skaičiai, institucijos, kurioms reikia dėmesio, būklės pokyčių grafikas ir posėdžių laiko juosta" caption="Koordinatorės vaizdas: jos padalinio rodikliai ir posėdžių laiko juosta." href="/mano/dashboard/atstovavimas/padaliniai" />

## Kaip tai veikia

### Kurių padalinių rodiklius matai {#rodikliai}

Rodikliai rodomi tų padalinių, kurių institucijas naudotojas gali matyti (žr. [Kas ką gali](#teises)). PKP
rodikliai nerodomi. Jei nė vieno padalinio rodiklių matyti negalima, puslapyje lieka tik posėdžių
laiko juosta.

Viršuje esančiu pasirinkimu **Rodikliai** galima nurodyti, kurių padalinių rodiklius rodyti. Pirmą kartą
pasirenkamas vienas naudotojo padalinys. Bent vienas visada lieka pasirinktas, o pasirinkimas
įsimenamas.

Institucijų būsenos skaičiuojamos taip pat kaip [Apžvalgoje](/visak/apzvalga#busenos).

### Kurios institucijos skaičiuojamos {#skaiciuojamos-institucijos}

Dabartiniuose rodikliuose skaičiuojamos tik **aktyvios institucijos**. Neaktyvių institucijų posėdžiai,
užduotys ir atstovai neprisideda prie dabartinių rodiklių, jei nesusiję ir su kita skaičiuojama institucija.
Jau užfiksuoti ankstesnių dienų rodikliai išsaugomi.


<ChangelogNote date="2026-10-20" title="Rodikliai skaičiuojami VU organams">

Rodikliai pagal nutylėjimą skaičiuojami tik VU organų institucijoms ir jų atstovams; VU SA organai rodomi posėdžių laiko juostoje.

</ChangelogNote>

Skaičiai, sąrašas **Reikia dėmesio**, būklės pokyčiai, taip pat [atstovų](/visak/atstovai) aktyvumas ir
atstovavimo rodikliai skaičiuojami tik tų organų institucijoms, kurie pažymėti
[atstovavimo nustatymuose](/sistema/nustatymai#atstovavimas) – pagal nutylėjimą tik **VU organams**
(Senatui, Tarybai, fakultetų taryboms ir pan.). Ar institucija yra VU, VU SA, nacionalinis ar
tarptautinis organas, nustato jos [tipas](/sistema/tipai). Atstovas skaičiuojamas, jei eina
studento atstovo pareigas tokioje institucijoje. Po skaičiais rodoma, kurie organai skaičiuojami.

Posėdžių laiko juostoje rodomos visos aktyvios institucijos, ir VU SA organai. Institucijos, kurių tipas
[posėdžių nustatymuose](/sistema/nustatymai#posedziai) neįtraukiamas į rodiklius, taip pat rodomos
laiko juostoje, bet neskaičiuojamos.

### Kurias institucijas matai laiko juostoje {#laiko-juosta}

Neaktyvios institucijos laiko juostoje nerodomos, įskaitant susijusias institucijas.


| Institucija | Kaip rodoma |
|---|---|
| Visos padalinio institucijos, jei rodomi jo rodikliai | Pilnai |
| Tavo institucijos ir tos, kurias administruoji kaip sekretorius | Pilnai |
| Institucijos, su kuriomis tavo institucija susieta [ryšiu](/sistema/rysiai) | Pilnai |
| Institucijos, kurios pačios susietos su tavo institucija (vienpusiu ryšiu) | Tik skaitymui, posėdžiai be darbotvarkių |
| Viešai posėdžiaujančios institucijos bet kuriame padalinyje | Tik skaitymui |

**Tik skaitymui** rodomos institucijos neturi būsenos, o jų posėdžiai neturi užpildymo informacijos
(ar įkeltas protokolas, ar užpildyta darbotvarkė). Kitų institucijų posėdžiai nerodomi visai.

## Veiksmai

### Skaičiai

| Skaičius | Ką rodo |
|---|---|
| **Vėluoja** | Pasirinktų padalinių institucijos, kurios vėluoja |
| **Artėja terminas** | Institucijos, kurioms artėja terminas |
| **Pranešimo laikotarpis baigėsi** | Institucijos, kurių pranešimas baigėsi, bet pusė periodiškumo dar nepraėjo |
| **Nėra duomenų** | Institucijos be jokios užregistruotos veiklos |

Kol duomenys kraunasi, vietoj skaičių rodomi pilki laukeliai, o ne klaidinantis 0.

### Reikia dėmesio

Viršuje vienoje eilutėje pateikiami keturi institucijų skaičiai. Žemiau kairėje rodomos tik institucijos, kurioms reikia veiksmų, o dešinėje – būklės grafikas; plačiame ekrane abiem dalims skirta po pusę pločio. Telefone dalys rodomos viena po kitos.

Institucijos, kurios vėluoja, neturi veiklos arba kurioms artėja terminas, svarbiausios pirmiausia.
Rodomos pirmos keturios, o likusias atveria **Rodyti visas** – ten jas galima ir ieškoti. Jei
pasirinkti keli padaliniai, prie kiekvienos institucijos parašytas jos padalinys. Mygtukas
**Fiksuoti veiklą** leidžia užregistruoti posėdį arba pranešti, kad posėdžio nebuvo. Mygtukas **Paklausti atstovų** pateikiamas po sąrašu, greta **Rodyti visas**.
Užklausos langas perima **Rodikliai** pasirinktus padalinius ir iš pradžių rodo tik **VU organus**.
Filtrus galima keisti; jų neatitinkančios institucijos nužymimos. Institucijų pasirinkime ir galutinėje
gavėjų peržiūroje yra bendro žymėjimo mygtukai. [Užklausų taisyklės ir 100 institucijų riba](/visak/institucijos#paklausti).

### Būklės pokyčiai {#busenos-pokyciai}

Grafikas rodo, kiek institucijų kiekvieną dieną buvo kiekvienos būsenos per pastarąsias **30, 90
arba 180 dienų** (numatytoji – 90).

**Posėdis skaičiuojamas tik nuo jo įvedimo.** Vėliau užregistruotas posėdis nepadengia ankstesnių dienų. Perkėlimai, ištrinti pranešimai apie veiklą ir ankstesni institucijų nustatymai ne visada atkuriami, todėl atkurtos dienos turi paaiškinimą grafiko užuominoje. Būsenų plotai rodomi švelnesnėmis nepermatomomis spalvomis; palyginimo kadencijos pabaigos užrašui palikta vietos virš jų.

Po plotu rodoma siaurų vienodo pločio stulpelių pokyčių juosta: grupavimas priklauso nuo laikotarpio ir grafiko pločio. Platesniame 90 dienų vaizde galima matyti atskirų dienų stulpelius; siauresniame vaizde kelios dienos sujungiamos, kad stulpeliai nesusiliestų. Paskutinis nepilnas intervalas lieka tokio pat pločio. Žalios juostelės virš vidurio linijos – kiek institucijų išėjo iš dėmesio sąrašo, raudonos po ja – kiek pateko į jį. Užvedus, palietus arba pasirinkus dieną klaviatūros rodyklėmis matyti tos dienos būsenų skaičiai, bendras institucijų skaičius ir pasirinkto intervalo pokyčių suma su jo pradžios bei pabaigos datomis. Punktyras žymi palyginimo kadencijos pabaigą, jei ji patenka į grafiko laikotarpį. Paaiškinimas rodomas virš puslapio turinio ir lieka ekrano ribose.

Praėjusios dienos išsaugomos kiekvienam padaliniui atskirai. Jei bent vieno pasirinkto padalinio dienos trūksta, grafike paliekamas tarpas ir paleidžiamas atkūrimas. Dalinis skaičius nerodomas kaip visų padalinių rezultatas. Šiandienos būsena skaičiuojama gyvai; įprastas pakartotinis istorijos pildymas jau išsaugotų dienų nekeičia.

**Pranešimo laikotarpis baigėsi** turi atskirą grafiko dalį: šios institucijos neįtraukiamos nei į
tvarkingų institucijų skaičių, nei į **Reikia dėmesio**. Laikotarpis skaičiuojamas pagal
[bendras būklės taisykles](/visak/apzvalga#busenos).

### Atstovai {#atstovai}

Po posėdžių laiko juosta rodoma, kiek pasirinktų padalinių atstovų buvo aktyvūs per 30 dienų, ir
nuoroda į [Atstovų](/visak/atstovai) puslapį su tais pačiais padaliniais. Ten rasi atstovus, kuriems
reikia dėmesio, jų aktyvumą ir [atstovavimo rodiklius](/visak/atstovai#atstovavimo-rodikliai).

### Posėdžių laiko juosta

Laiko juosta rodoma didesniame ekrane, o telefone siūloma nuoroda į posėdžių sąrašą. Posėdžiai
kraunami dalimis: kai slenki ar atitolini vaizdą, įkeliami matomi mėnesiai, artimiausi ekrano viduriui
pirmiausia.

Laiko juostos padaliniai pasirenkami mygtuku **Rodoma** šalia filtrų. Kai puslapio viršuje pakeičiamas
rodiklių padalinių pasirinkimas, laiko juosta pradeda rodyti tuos pačius padalinius, taip pat ir kitą
kartą atvėrus puslapį. Laiko juostoje galima laikinai pridėti daugiau padalinių – rodiklių pasirinkimas
nuo to nesikeičia.

**Filtrai** – tai rodymo nustatymai: rodyti tik aktyvias ar tik viešas institucijas, slėpti VU SA
darinius, rodyti narius ir užklausas atstovams. **Atstatyti filtrus** grąžina numatytuosius
nustatymus.

Laiko juostos įrankių juostoje:

- **Mastelis** keičiamas mygtukais, slankikliu arba klavišais **+** ir **−** – nuo maždaug
  trejų metų iki kelių mėnesių viename ekrane.
- **Rikiuoti** keičia institucijų tvarką: pagal pavadinimą, **paskutinę veiklą**, **artimiausią
  posėdį** arba **paskutinį įvykusį posėdį**. Paskutinė veikla – vėliausias posėdžio, jo darbotvarkės
  klausimo ar balsavimo pakeitimas arba pranešimas apie veiklą. Institucijos be tokios datos rodomos
  gale. Tvarka galioja kiekvienoje grupėje. Data šalia institucijos pavadinimo rodo tai, pagal ką
  rikiuojama; rikiuojant pagal pavadinimą – vėliausią iš artimiausio ar paskutinio posėdžio ir
  pranešimo „Posėdžio nebuvo“ pabaigos. Užvedus ant datos matyti, kas tai per data.
- **Grupuoti** sudeda institucijas **pagal padalinius**, **pagal institucijų tipus**, **pagal
  artimiausią posėdį** arba palieka vienu sąrašu. Institucija, turinti kelis tipus, rodoma prie
  kiekvieno, o institucijos be tipo – sąrašo gale, skiltyje **Be tipo**. Grupuojant pagal artimiausią
  posėdį skiltys eina laiko tvarka: **Šią savaitę**, **Kitą savaitę**, **Šį mėnesį**, **Vėliau**,
  tada **Praėjusi veikla** (suplanuoto posėdžio nėra, bet yra ankstesnių posėdžių ar pranešimų) ir
  **Nėra informacijos**.
- **Legenda** (ⓘ šalia institucijų skaičiaus) paaiškina spalvas ir žymėjimus. Laiko juostą galima
  atidaryti per visą ekraną, o institucijų stulpelio plotį keisti tempiant jo kraštą.

**Užklausos atstovams.** Tie, kurie gali [klausti atstovų](/visak/institucijos#paklausti), mato
visas dar neatsakytas užklausas, o atstovai – tik jiems išsiųstas. Tokios institucijos pavadinimas
pažymėtas mėlynu laiško ženklu. Užklausa **Ar vyko posėdis?** rodoma institucijos eilutėje kaip
mėlyna punktyrinė juosta per klausiamą laikotarpį su laiško ženklu viduryje; užvedus ant ženklo
matyti, kada užklausa išsiųsta, iki kada galioja ir kiek gavėjų. Užklausa **Papildyk posėdžių
įrašus** pažymi pačius neužpildytus to laikotarpio posėdžius mėlyna spalva ir laiško ženklu.

<ChangelogNote date="2026-10-18" title="Grupavimas pagal tipus ir užklausos laiko juostoje">

Laiko juostą galima grupuoti pagal institucijų tipus, ji seka rodiklių padalinių pasirinkimą, rodo
neatsakytas užklausas atstovams ir greičiau kraunasi atitolinus vaizdą. Išsamios eilutės pašalintos.

</ChangelogNote>

## Kas ką gali {#teises}

Puslapį atidaryti gali **kiekvienas** prisijungęs narys.

| Rolė | Rodikliai | Laiko juosta | Atviros užduotys |
|---|---|---|---|
| Narys be rolės | – | Viešos, savo ir susijusios institucijos | – |
| Studentų atstovas | – | Viešos, savo ir susijusios institucijos | – |
| Studentų atstovų koordinatorius | Savo padalinio | Savo padalinys pilnai, kitur – viešos | – |
| Komunikacijos koordinatorius | Savo padalinio | Savo padalinys pilnai, kitur – viešos | – |
| Centrinio biuro studentų atstovų koordinatorius | Visų padalinių | Visi padaliniai pilnai | ✓ |

Laiko juosta atsidaro su tais pačiais padaliniais, kurių rodikliai rodomi. Kas rodiklių nemato, tam
laiko juosta pirmą kartą atsidaro su jo institucijų padaliniais, o vėliau – su paskutiniu
pasirinkimu.

## Techninė informacija {#technine-informacija}

- Būklės istorija – `InstitutionStatusReplayService` atkuria kiekvienos dienos būsenas pagal tuo metu
  jau įvestus įrašus (posėdis skaičiuojamas nuo `created_at` iki `deleted_at`, pranešimas apie veiklą –
  nuo `created_at`), o klasifikuoja `InstitutionActivityStatusService::classify`. Uždarytas dienas
  kasnakt į `institution_status_snapshots` (po eilutę padaliniui ir dienai) įrašo
  `metrics:reps:snapshot`; `--from=YYYY-MM-DD` užpildo trūkstamas dienas ir pažymi jas kaip
  atkurtas; `--repair-reconstructed` taiso tik atkurtas eilutes. Istorijos užklausa skaito pilnas išsaugotas dienas, trūkstamas perduoda foninei užduočiai, o šiandieną skaičiuoja gyvai.
- Skaičiuojamas institucijas parenka `RepStatisticsScope::institutions()` (tipų išimtys ir `RepMetricSettings::counted_institution_scopes`, SQL filtras `InstitutionScopeResolver::constrainToScopes()`); laiko juosta šio filtro netaiko, eilutės pažymimos `in_summary`.
- Puslapis – `AtstovavimasDashboardController::padaliniai` (`statsTenants`, `ganttTenants`,
  `defaultGanttTenantIds`, `canViewTenantTasks`). Duomenys kraunami per
  `/api/v1/admin/visak/{timeline,timeline/history,gantt,meetings,activity-requests}`
  (`AtstovavimasApiController`). Posėdžiai kraunami ne didesnėmis kaip 6 mėn. dalimis
  (`useTenantMeetings`); serveris priima iki 24 mėn.
- Užklausos atstovams – `GetOpenActivityRequestSpans`: atviros (`InstitutionActivityRequest::open()`)
  užklausos, sutrauktos į vieną laikotarpį institucijai ir užklausos rūšiai. Visas mato tas, kas
  praeina `InstitutionCheckInPolicy::askAboutActivity` (`ResolveAskableInstitutions`), kiti – tik
  savo. Atsakymas nekešuojamas.
- Rodiklių padaliniai – `AtstovavimasSettings::getVisibleTenantIds`: super administratorius arba
  `institutions.read.*` – visi, `institutions.read.padalinys` – savo. Rodiklių užklausos kitiems
  padaliniams grąžina 403. Užduočių skaičius – `tasks.read.padalinys`.
- Laiko juostos prieiga – `AtstovavimasDashboardService::ganttInstitutionAccess` (`full`, `public`,
  `restricted`). Viešos institucijos – `MeetingSettings` viešų posėdžių tipai. Rodikliai neįtraukia
  `MeetingSettings` išskirtų institucijų tipų.
- Talpykla: laiko juosta ir posėdžiai – 10 min., būklės istorija – 1 val., `refresh=1` ją aplenkia.
  Raktas sudaromas iš prieigos žemėlapio, todėl pilni duomenys neatiduodami tam, kas mato tik
  viešus.
