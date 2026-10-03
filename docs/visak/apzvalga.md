---
doc_status: reviewed
title: Apžvalga
area: dashboard
last_reviewed: 2026-09-30
tests:
  - tests/Feature/Admin/Dashboard/AtstovavimasDashboardTest.php
  - tests/Feature/Admin/Core/CoordinatorOnRepScreensTest.php
  - tests/Feature/Admin/Core/AtstovavimasSettingsTest.php
  - tests/Feature/Responsibilities/ResponsibilityResolverTest.php
  - tests/Feature/InstitutionSubscriptionTest.php
  - tests/Unit/InstitutionActivityStatusServiceTest.php
  - tests/Unit/Enums/InstitutionActivityStatusTest.php
  - tests/Unit/Services/AcademicCalendarServiceTest.php
  - tests/Unit/Services/MeetingCompletionServiceTest.php
  - resources/js/Pages/Admin/Dashboard/__tests__/ShowAtstovavimas.component.test.ts
  - resources/js/Components/Home/__tests__/HomeSections.component.test.ts
  - resources/js/Pages/Admin/Dashboard/Composables/__tests__/useTimelineFilters.test.ts
  - resources/js/Pages/Admin/Dashboard/Composables/__tests__/useAtstovavimasData.test.ts
  - resources/js/Pages/Admin/Dashboard/Components/__tests__/TenantScopeSelector.component.test.ts
  - resources/js/Pages/Admin/Dashboard/Components/__tests__/UserTimelineSection.component.test.ts
  - tests/Browser/VisakOverviewPagesTest.php
  - tests/Browser/GanttDateStackingTest.php
---

# Apžvalga

**ViSAK → Apžvalga** (`/mano/dashboard/atstovavimas`) yra studento atstovo darbo pradžia. Ji
atsako į klausimą: *kurioms mano institucijoms reikia dėmesio ir kada kitas posėdis?* Puslapis
**asmeninis**: kiekvienas mato savo institucijas, savo posėdžius ir savo užduotis, nepriklausomai
nuo rolės. Viso padalinio vaizdas yra [Padalinių apžvalgoje](/visak/padaliniai).

<DocScreenshot name="visak-overview" alt="ViSAK apžvalga: skaičiai, institucijos, kurioms reikia dėmesio, artimiausi posėdžiai ir laiko juosta" caption="Atstovės apžvalga: vienai institucijai trūksta veiklos, o du posėdžiai laukia užpildymo." href="/mano/dashboard/atstovavimas" />

## Kaip tai veikia

<ChangelogNote version="v3.0" date="2026-10-02" title="Tavo institucijos ir padalinių rodikliai atskirti">

Ši apžvalga skirta tavo institucijoms ir toms, kurių sekretorius esi. Viso padalinio rodiklius bei laiko juostą atverk skiltyje **Padaliniai**; apžvalgoje seno „Mano institucijos / Padalinys“ jungiklio nėra.

</ChangelogNote>

**Tavo institucijos** yra institucijos, kuriose eini pareigas, ir institucijos, kurių
**sekretorius** esi, nors nesi jų narys. Pastarosios pažymėtos ženklu **Sekretorius**. Kiekviena
institucija turi **veiklos būseną**, kuri parodo, ar apie jos veiklą laiku pranešama.

### Institucijos būsena {#busenos}

Kiekviena institucija turi **periodiškumą** – kas kiek dienų tikimasi posėdžio ar pranešimo apie
veiklą. Jis imamas iš institucijos nustatymų. Jei jis nenustatytas, imamas trumpiausias iš
institucijos tipų periodiškumų, o jei ir jų nėra – **30 dienų**.

Būsena nustatoma taip (tikrinama iš eilės, galioja pirmoji tinkanti):

| Būsena | Kada |
|---|---|
| **Suplanuotas posėdis** | Institucija turi būsimą posėdį |
| **Užfiksuotas kontaktas** | Šiandien galioja pranešimas apie veiklą (pvz., kad posėdžių nebus per sesiją) |
| **Nėra veiklos** | Institucija neturi nė vieno praėjusio posėdžio ar pranešimo |
| **Vėluoja** | Nuo paskutinės veiklos praėjo daugiau dienų nei periodiškumas |
| **Artėja terminas** | Praėjo bent 80 % periodiškumo |
| **Aktyvi** | Visais kitais atvejais |

**Paskutinė veikla** yra vėliausias praėjęs posėdis arba pasibaigęs pranešimas apie veiklą. Po
pranešimo dienos skaičiuojamos nuo kitos dienos po jo pabaigos.

Dienos skaičiuojamos **be akademinių atostogų**: liepos 1 – rugpjūčio 31 d., gruodžio 24 – sausio 1 d.,
nuo paskutinio sausio pirmadienio iki vasario 4 d. ir Velykų atostogų (savaitė prieš Velykas ir dvi
dienos po jų). Todėl po vasaros institucija iš karto „nevėluoja“.

**Dėmesio reikia** institucijoms, kurių būsena yra **Vėluoja**, **Nėra veiklos** arba **Artėja
terminas**. Jos rikiuojamos būtent tokia tvarka.

## Veiksmai

### Skaičiai

Viršuje rodomi keturi skaičiai. Kiekvienas yra nuoroda į atitinkamą sąrašą:

| Skaičius | Ką skaičiuoja | Kur veda |
|---|---|---|
| **Vėluoja** | Tavo institucijos, kurios vėluoja | Institucijų sąrašas |
| **Artėja terminas** | Tavo institucijos, kurioms artėja terminas | Institucijų sąrašas |
| **Neužpildyti posėdžiai** | Tavo institucijų posėdžiai, kurių darbotvarkėje yra neužpildytų klausimų | Posėdžių sąrašas |
| **Atviros užduotys** | Tavo neatliktos užduotys | [Mano → Užduotys](/mano/uzduotys) |

### Tavo institucijos

Institucijos, kurioms **reikia dėmesio** (žr. [būsenas](#busenos)). Prie kiekvienos rodoma būsena,
prieš kiek dienų buvo paskutinė veikla ir mygtukas **Fiksuoti veiklą**. Jis leidžia užregistruoti
posėdį arba pranešti, kad posėdžio nebuvo. Jei visos institucijos tvarkingos, rodoma viena eilutė,
kad viskas laiku.

### Artimiausi posėdžiai

Tavo institucijų ir sekamų institucijų posėdžiai **nuo šiandienos pradžios
iki dviejų mėnesių į priekį**, artimiausi pirmiausia. Rodomi trys, o kitus atveria **Rodyti visus**.
Sekamos institucijos posėdis pažymėtas **Seki**.

### Tavo koordinatorius {#tavo-koordinatorius}

Šalia rodomas tavo [koordinatorius](/visak/#koordinatorius) ir mygtukas parašyti jam laišką. Jis
rodomas tik jei eini pareigas bent viename **VU organe**. Jei tavo VU organai yra keliuose
padaliniuose, rodomas kiekvieno padalinio koordinatorius ir kurias tavo institucijas jis kuruoja
(VU SA dariniai į šį sąrašą neįtraukiami). Laiškas rašomas koordinatoriaus pareigybės el. paštu, o
jei jos nėra – jo paties el. paštu. Jei institucijai nėra dabar pareigas einančio [atsakingo
koordinatoriaus](/pagrindai/atsakomybes), kortelė nerodoma. Pats sau
koordinatoriumi niekas nerodomas.

### Dokumentai

Iki **8** dokumentų, pridėtų prie tavo pareigybių tipų (ir jų aukštesnių tipų), pvz., nuostatai ar
atmintinės. Naujausi (pagal dokumento datą) pirmiausia. Jei tokių dokumentų nėra, skiltis nerodoma.

### Posėdžių laiko juosta

Didesniame ekrane rodoma tavo institucijų posėdžių laiko juosta. Telefone vietoj jos siūloma nuoroda
į posėdžių sąrašą. **Rodymo nustatymuose** galima, pavyzdžiui, parodyti **susijusias institucijas**.
Susijusios institucijos, kurių duomenų matyti negali, rodomos be darbotvarkių. Laiko juostą galima
atidaryti per visą ekraną.

### Sekamos institucijos

Institucijos, kurias seki (sekti galima institucijų sąraše arba institucijos puslapyje), su jų būsena ir nuoroda į visų sekamų sąrašą.

### Viskas tvarkoje

Tuščios skiltys (pvz., jokia institucija nereikalauja dėmesio) neužima vietos. Jos surašomos
puslapio apačioje po vieną eilutę.

### Padalinių pasirinkimas

Jei tavo institucijos yra keliuose padaliniuose, viršuje galima pasirinkti, kurių padalinių
institucijas rodyti. Bent vienas padalinys lieka pasirinktas. Pasirinkimas įsimenamas.

::: tip Pirmas apsilankymas
Pirmą kartą atsidarius puslapį, trumpas turas parodo pagrindines jo dalis.
:::

## Kas ką gali {#teises}

Apžvalgą atidaryti gali **kiekvienas** prisijungęs narys. Ką joje matai, lemia ne rolė, o tai, kokias
pareigas eini:

| | Studentų atstovas | Koordinatorius | Narys be institucijų |
|---|---|---|---|
| Tavo institucijos ir jų būsenos | Savo institucijų | Savo institucijų | Tuščia |
| Artimiausi posėdžiai | Savo ir sekamų institucijų | Savo ir sekamų institucijų | Tik sekamų |
| Tavo koordinatorius | Savo padalinio koordinatorius | Kiti koordinatoriai, jei yra | – |

Viso padalinio duomenys (visų institucijų būsenos, atstovų aktyvumas) yra
[Padalinių apžvalgoje](/visak/padaliniai).

## Techninė informacija {#technine-informacija}

- Puslapis – `AtstovavimasDashboardController::atstovavimas`, komponentas `Admin/Dashboard/ShowAtstovavimas`.
  Seni adresai `?scope=tenant` ir `?tab=tenant` nukreipia į `dashboard.atstovavimas.padaliniai`.
- Institucijos – `DutyService::getUserInstitutionsForDashboard`, būsena –
  `InstitutionActivityStatusService::resolve` (80 % riba), atostogos – `AcademicCalendarService`,
  periodiškumas – `Institution::meeting_periodicity_days`.
- Artimiausi posėdžiai – `GetUpcomingMeetingsForUser` (iki 20 eilučių, `total` – visų skaičius).
- Koordinatoriai (`GetUserCoordinators`), dokumentai (`GetTypeFiles`, riba
  `REFERENCE_DOCUMENTS_LIMIT` = 8) ir sekamos institucijos kraunami atidėtai (`secondary` grupė).
  Susijusios institucijos kraunamos tik įjungus jų rodymą (`relatedInstitutions`).
- Koordinavimo šaltinį ir dabartinius pareigybės narius nustato `ResponsibilityResolver`.
