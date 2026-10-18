---
doc_status: reviewed
title: Padaliniai
area: visak
last_reviewed: 2026-10-18
tests:
  - tests/Feature/Admin/Dashboard/AtstovavimasDashboardTest.php
  - tests/Feature/Admin/Core/AtstovavimasApiTest.php
  - tests/Feature/Admin/Core/AtstovavimasSettingsTest.php
  - tests/Unit/InstitutionActivityStatusServiceTest.php
  - tests/Unit/Enums/InstitutionActivityStatusTest.php
  - resources/js/Pages/Admin/Dashboard/__tests__/ShowAtstovavimasPadaliniai.component.test.ts
  - resources/js/Pages/Admin/Dashboard/Components/__tests__/TenantScopeSelector.component.test.ts
  - resources/js/Pages/Admin/Dashboard/Components/__tests__/GanttFilterDropdown.component.test.ts
  - resources/js/Pages/Admin/Dashboard/Components/__tests__/InstitutionStatusTrendChart.component.test.ts
  - resources/js/Pages/Admin/Dashboard/Composables/__tests__/useTimelineFilters.test.ts
  - resources/js/Pages/Admin/Dashboard/Composables/__tests__/useTenantMeetings.test.ts
  - resources/js/Pages/Admin/Dashboard/Composables/__tests__/useGanttSettings.test.ts
  - resources/js/Pages/Admin/Dashboard/Components/__tests__/TenantTimelineSection.component.test.ts
  - resources/js/Components/Graphs/__tests__/MeetingsGantt.component.test.ts
  - resources/js/Components/Graphs/composables/__tests__/useGanttLayout.test.ts
  - resources/js/Components/Graphs/composables/__tests__/useGanttInteractions.test.ts
  - resources/js/Pages/Admin/Dashboard/Composables/__tests__/statusTrend.test.ts
  - resources/js/Components/Home/__tests__/HomeSections.component.test.ts
  - tests/Browser/VisakOverviewPagesTest.php
---

# Padaliniai

**ViSAK → Padaliniai** (`/mano/dashboard/atstovavimas/padaliniai`) yra viso padalinio vaizdas:
kurioms institucijoms reikia dėmesio, kaip keičiasi jų būklė, kaip aktyvūs atstovai ir kada vyksta
posėdžiai. Puslapį sudaro dvi dalys:

- **Rodikliai** – skaičiai, dėmesio sąrašas, būklės pokyčiai ir atstovų aktyvumas. Jie rodomi tik
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
Institucijos, kurių tipas [posėdžių nustatymuose](/sistema/nustatymai) neįtraukiamas į rodiklius,
rodomos laiko juostoje, bet neįskaičiuojamos nei į skaičius, nei į sąrašą **Reikia dėmesio**.

### Kurias institucijas matai laiko juostoje {#laiko-juosta}

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
| **Nėra duomenų** | Institucijos be jokios užregistruotos veiklos |
| **Atviros užduotys** | Neatliktos padalinio atstovų užduotys, rodoma tik tiems, kas gali matyti [užduočių suvestinę](/visak/uzduociu-suvestine) |

Kol duomenys kraunasi, vietoj skaičių rodomi pilki laukeliai, o ne klaidinantis 0.

### Reikia dėmesio

Institucijos, kurios vėluoja, neturi veiklos arba kurioms artėja terminas, svarbiausios pirmiausia.
Rodomos pirmos penkios, o likusias atveria **Rodyti visas** – ten jas galima ir ieškoti. Jei
pasirinkti keli padaliniai, prie kiekvienos institucijos parašytas jos padalinys. Mygtukas
**Fiksuoti veiklą** leidžia užregistruoti posėdį arba pranešti, kad posėdžio nebuvo.

### Būklės pokyčiai

Grafikas rodo, kiek institucijų kiekvieną dieną buvo kiekvienos būsenos per pastarąsias **30, 90
arba 180 dienų** (numatytoji – 90). Po grafiku parašyta, ar vėluojančių institucijų per tą laiką
padaugėjo, sumažėjo, ar jų skaičius nesikeitė.

### Atstovų aktyvumas

Kiek atstovų prisijungė **šiandien**, **per 7 d.**, **per 30 d.** ir kiek **niekada** neprisijungė.
Mygtukas **Visi atstovai** atveria sąrašą, kuriame galima ieškoti atstovų ir atsirinkti aktyvius
arba neaktyvius (niekada neprisijungusius arba neprisijungusius daugiau nei 30 d.).

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
- **Grupuoti** sudeda institucijas **pagal padalinius**, **pagal institucijų tipus** arba palieka
  vienu sąrašu. Institucija, turinti kelis tipus, rodoma prie kiekvieno, o institucijos be tipo –
  sąrašo gale, skiltyje **Be tipo**.
- Laiko juostą galima atidaryti per visą ekraną, o **legenda** paaiškina spalvas ir žymėjimus.
  Institucijų stulpelio plotį galima keisti tempiant jo kraštą.

**Užklausos atstovams.** Tie, kurie gali [klausti atstovų](/visak/institucijos#paklausti), mato
visas dar neatsakytas užklausas, o atstovai – tik jiems išsiųstas. Tokios institucijos pavadinimas
pažymėtas mėlynu laiško ženklu. Užklausa **Ar vyko posėdis?** rodoma institucijos eilutėje kaip
mėlyna punktyrinė juosta per klausiamą laikotarpį su laiško ženklu viduryje; užvedus ant ženklo
matyti, kada užklausa išsiųsta, iki kada galioja ir kiek gavėjų. Užklausa **Papildyk posėdžių
įrašus** pažymi pačius neužpildytus to laikotarpio posėdžius mėlyna spalva ir laiško ženklu.

<ChangelogNote version="v3.8" date="2026-10-18" title="Grupavimas pagal tipus ir užklausos laiko juostoje">

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

- Puslapis – `AtstovavimasDashboardController::padaliniai` (`statsTenants`, `ganttTenants`,
  `defaultGanttTenantIds`, `canViewTenantTasks`). Duomenys kraunami per
  `/api/v1/admin/visak/{timeline,timeline/history,representatives,gantt,meetings,activity-requests}`
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
