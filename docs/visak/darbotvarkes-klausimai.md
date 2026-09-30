---
doc_status: reviewed
title: Darbotvarkės klausimai
area: agendaItems
models: [AgendaItem, Vote]
last_reviewed: 2026-09-30
tests:
  - tests/Feature/Admin/Problems/AgendaItemProblemControllerTest.php
  - tests/Feature/Admin/Calendar/AgendaItemControllerTest.php
  - tests/Feature/Admin/Calendar/AgendaItemNoteTest.php
  - tests/Feature/Admin/Discussions/AgendaItemPageAccessTest.php
  - tests/Feature/Admin/Calendar/MeetingControllerTest.php
  - tests/Feature/VoteControllerTest.php
  - tests/Feature/Meetings/AgendaItemVotesTest.php
  - tests/Feature/Meetings/AgendaItemBreakTest.php
  - tests/Feature/Meetings/AgendaItemTimetableTest.php
  - tests/Feature/Meetings/MeetingStudentPerspectiveTest.php
  - tests/Feature/Meetings/MeetingTranslationsTest.php
  - tests/Feature/AgendaItemSearchableTest.php
  - tests/Feature/Admin/Search/SearchControllerTest.php
  - tests/Feature/Admin/Search/SearchVisibilityParityTest.php
  - tests/Feature/Tasks/Subscribers/MeetingTaskSubscriberTest.php
  - tests/Unit/Services/MeetingCompletionServiceTest.php
  - resources/js/Composables/__tests__/useAgendaItemStyling.test.ts
  - resources/js/Composables/__tests__/useAgendaItemAutosave.test.ts
  - resources/js/Components/AgendaItems/__tests__/AgendaItemBody.component.test.ts
  - resources/js/Components/AgendaItems/__tests__/AgendaItemVotes.component.test.ts
  - resources/js/Components/AgendaItems/__tests__/AgendaItemVotesSheetForm.component.test.ts
  - resources/js/Components/AgendaItems/__tests__/AgendaItemSheetForm.component.test.ts
  - resources/js/Pages/Admin/Representation/__tests__/ShowAgendaItem.component.test.ts
  - resources/js/Components/Meetings/__tests__/MeetingAgendaList.component.test.ts
  - tests/Browser/VisakRecordPagesTest.php
---

# Darbotvarkės klausimai

Darbotvarkės klausimas (punktas) – vienas posėdyje svarstomas klausimas. Jis priklauso vienam
[posėdžiui](/visak/posedziai), o prie jo žymima, kas nuspręsta, kaip balsavo studentų atstovai ir
ar sprendimas palankus studentams. Taip kaupiama istorija, kaip institucijose sprendžiamos
panašios temos.

Visų posėdžių klausimų sąrašas pasiekiamas adresu `/mano/agendaItems`, o kiekvienas klausimas turi
savo puslapį `/mano/agendaItems/{id}`.

<DocScreenshot name="agenda-item-record" alt="Darbotvarkės klausimo puslapis: būsena, institucija, posėdis, rezultato laukai Sprendimas, Studentų balsas ir Nauda studentams bei atstovų pastabos" caption="Klausimas, kuriam dar nepažymėta nauda studentams." />

## Kaip tai veikia

### Klausimo tipas {#tipai}

Kiekvienam klausimui pažymimas tipas. Kol jis nepažymėtas, klausimas laikomas nepilnu.

| Tipas | Kada | Reikia balsavimo? |
|---|---|---|
| **Balsavimas** | Dėl klausimo priimamas sprendimas | Taip |
| **Informacinis** | Klausimas tik pristatomas | Ne |
| **Atidėtas** | Klausimas perkeltas į kitą posėdį | Ne |
| **Pertrauka** | Pertrauka tarp klausimų | Ne |

Pasirinkus **Balsavimas**, automatiškai pridedamas pagrindinis balsavimas.

### Balsavimai {#balsavimai}

Klausimas gali turėti kelis balsavimus (pvz., dėl atskirų pataisų). Vienas jų yra **pagrindinis** –
pagal jį nustatoma klausimo būsena. Pirmas balsavimas tampa pagrindiniu automatiškai; ištrynus
pagrindinį, pagrindiniu tampa kitas. Balsavimų tvarka, pavadinimai ir pagrindinis keičiami lange
**Tvarkyti balsavimus**.

Kiekviename balsavime žymimi trys laukai:

| Laukas | Reikšmės |
|---|---|
| **Sprendimas** – ar institucija klausimą priėmė | Priimtas · Atmestas · Susilaikyta |
| **Studentų balsas** – kaip balsavo studentų atstovai | Pritarė · Nepritarė · Susilaikė |
| **Nauda studentams** – ar sprendimas palankus studentams | Palanku · Nepalanku · Neutralu |

Tais pačiais žodžiais reikšmės rodomos ir sąrašo filtruose. **Bendru sutarimu** pažymi visus tris
laukus teigiamai. Jis išsijungia pats, jei pakeiti sprendimą arba studentų balsą (bet ne naudą
studentams).

### VU SA darinių išimtis {#vusa-isimtis}

Kai posėdis vyksta **VU SA darinyje** (parlamente, valdyboje ir pan.), atstovai ir yra
organizacija, todėl žymimas **tik sprendimas**. Jei posėdis bendras VU SA darinio ir VU organo
(ar kito išorinio organo), reikia visų trijų laukų. Institucijos rūšis aprašyta puslapyje
[ViSAK](/visak/#institucijos-rusis).

### Kada klausimas užpildytas {#uzpildyta}

Klausimas **užpildytas**, kai:

- pažymėtas jo tipas **ir**
- tipas nereikalauja balsavimo (Informacinis, Atidėtas, Pertrauka) **arba** pagrindiniame
  balsavime užpildyti visi reikiami laukai (VU SA dariniuose – tik sprendimas).

Papildomi balsavimai užpildymui įtakos neturi. Ta pati taisyklė taikoma posėdžio
[būsenai](/visak/posedziai#busena), užpildymo užduočiai ir sąrašo filtrui **Užpildymo būsena**.

<ChangelogNote version="v2.26" date="2026-09-27" title="Viena užpildymo taisyklė">

Iki v2.26 sąrašo filtras informacinius, atidėtus klausimus, pertraukas ir VU SA darinių klausimus su
vien sprendimu laikė nepilnais, o posėdžio būsena klausimą be tipo galėjo laikyti užpildytu.

</ChangelogNote>

### Klausimo būsena

Klausimo būsena rodo **rezultatą**, pvz., **Studentų pozicija priimta** (studentų balsas sutampa
su sprendimu), **Studentų pozicija nesutampa**, **Pritarta bendru sutarimu**, **Neaptartas** (dar
nėra sprendimo, o išoriniame organe – ir studentų balso), **Informacinis**, **Atidėtas**,
**Pertrauka** ar **Nepažymėtas** (nėra tipo). VU SA darinyje rodomas pats sprendimas: **Priimtas**,
**Atmestas** arba **Neutralus sprendimas**.

Būsena gali būti „Studentų pozicija priimta“, nors dar nepažymėta nauda studentams. Ar klausimui
dar kažko trūksta, rodo posėdžio **Nepilnų punktų** sąrašas.

### Laikas, aprašymas ir pastabos

- **Laikas** – nebūtinas; formatas `HH:MM`. Pabaiga turi būti vėlesnė už pradžią; galima nurodyti
  tik pabaigą. Pradžia siūloma pagal ankstesnio klausimo pabaigą arba posėdžio pradžią.
- **Aprašymas** ir **Studentų pozicija** – laisvas tekstas. Pavadinimas ir tekstai saugomi
  lietuviškai; angliškas vertimas pridedamas atskirai, o jo nesant rodomas lietuviškas tekstas.
- **Iškėlė: Studentų atstovai** – žymima, jei klausimą į darbotvarkę įtraukė studentai.
- **Atstovų pastabos** (privačios) – bendras atstovų užrašų laukas. Jį mato visi, kas gali matyti
  visą klausimo puslapį, o pildo – galintys redaguoti klausimą. Jame galima paminėti posėdžio
  atstovus (@). Pastabos nerodomos viešai ir tiems, kas mato tik viešą posėdį.

## Veiksmai

### Rezultato žymėjimas

Klausimo puslapyje rezultatas žymimas **vienu paspaudimu** – redagavimo režimo nėra, pakeitimai
išsaugomi automatiškai. Pažymėtas atsakymas susitraukia; jį galima pakeisti arba grąžinti į
„nefiksuota“. Pavadinimą, aprašymą, laiką ir studentų poziciją keisk mygtuku **Redaguoti punktą**.
Rodyklėmis viršuje pereinama prie kito posėdžio klausimo.

### Klausimų pridėjimas ir tvarka

Klausimai pridedami posėdžio puslapyje (žr. [Posėdžiai](/visak/posedziai)); nauji atsiranda
darbotvarkės gale. Tvarką keisk posėdžio darbotvarkėje (**Keisti tvarką**).

### Ištrynimas

Klausimas ištrinamas **negrįžtamai** – jis nepatenka į šiukšlinę. Ištrynus posėdį, jo klausimai
išsaugomi kartu su juo šiukšlinėje.

### Susijusios problemos {#problemos}

Skiltyje **Susijusios problemos** klausimą susieji su [problema](/visak/problemos), kurią atstovai
jame kėlė: spausk **Susieti problemą** ir pasirink iš atvirų ar vykdomų problemų. Problemai kartu
priskiriamos posėdžio institucijos, jei jos yra problemos padalinyje, o problemos puslapyje
klausimas matomas skirtuke **Svarstyta posėdžiuose**. **Atsieti** panaikina ryšį.

### Klausimų sąrašas

Sąraše (`/mano/agendaItems`) matyti visų tau prieinamų posėdžių klausimai. Pirmą kartą rodomi tavo
padalinių klausimai. Filtrai: **Posėdžio
metai**, **Užpildymo būsena**, **Studentų balsas**, **Sprendimas**, **Palankumas studentams**,
**Pateikė studentai**, **Balsavimo atitikimas** ir **Padalinys**.

## Kas ką gali {#teises}

| Veiksmas | Bet kuris narys | Institucijos narys posėdžio metu | Studentų atstovas | Studentų atstovų / Komunikacijos koordinatorius | Centrinio biuro studentų atstovų koordinatorius |
|---|---|---|---|---|---|
| Matyti viešo posėdžio klausimus (be pastabų) | ✓ | ✓ | ✓ | ✓ | ✓ |
| Matyti klausimą su pastabomis | – | ✓, tik skaityti | ✓, savo institucijų | ✓, savo padalinio | ✓ |
| Pridėti klausimų | – | – | ✓, savo institucijų | ✓, savo padalinio | ✓ |
| Keisti tvarką | – | ✓ | ✓, savo institucijų | ✓, savo padalinio | ✓ |
| Žymėti rezultatą, redaguoti, ištrinti | – | – | ✓, savo institucijų | ✓, savo padalinio | ✓ |
| Susieti ir atsieti problemas | – | – | ✓, savo institucijų | ✓, savo padalinio | ✓ |

Institucijos narys be rolės klausimo redaguoti negali, nors gali keisti patį posėdį.

## Pranešimai ir automatizavimas {#pranesimai}

- Pridėjus pirmą klausimą, atstovų užduotis „Sukurti posėdžio darbotvarkės klausimus“ užbaigiama,
  o sukuriama užduotis **„Užpildyti darbotvarkės klausimų informaciją“**.
- Užduoties eiga (pvz., „1 iš 3“) skaičiuojama pagal [užpildytus](#uzpildyta) klausimus ir
  atnaujinama kaskart išsaugojus klausimą ar balsavimą. Užpildžius visus, užduotis užbaigiama, o
  koordinatoriai ir institucijos sekėjai gauna pranešimą, kad darbotvarkė užpildyta.
- Jei užpildytas klausimas vėl tampa nepilnas (pvz., pakeistas į Balsavimą), užduotis atveriama.

Plačiau – [Posėdžiai](/visak/posedziai#pranesimai) ir [Užduotys](/mano/uzduotys).

## Techninė informacija {#technine-informacija}

### Teisės

- Klausimų teises sprendžia `AgendaItemPolicy`. `view` – narys posėdžio metu, ryšiu susijusios
  institucijos arba `agendaItems.read.{*|own|padalinys}`; `viewSummary` – posėdis viešas arba
  `view`. `update` ir `delete` – tik `agendaItems.{update|delete}.{*|own|padalinys}`.
- Kurti: `agendaItems.create.padalinys` **ir** posėdžio `update`. Perrikiuoti: posėdžio `update`.
- Balsavimai autorizuojami per klausimo `update` (`StoreVoteRequest`).
- Rolės: Studentų atstovas – `agendaItems.create.padalinys`, `read|update|delete.own`; Studentų
  atstovų ir Komunikacijos koordinatoriai – visos `.padalinys`; Centrinio biuro studentų atstovų
  koordinatorius – visos `*`.
- Sąrašo eilutes riboja Typesense raktas pagal `meetings.read.*` teises (ne `agendaItems.*`).

### Kaip tai įgyvendinta

- Tipai: `AgendaItemType`; `requiresVote()` – vienintelė vieta, nusprendžianti, kuriems reikia
  balsavimo.
- Užpildymas: `MeetingCompletionService::itemIsComplete()`; jį naudoja posėdžio būsena,
  `AgendaCompletionTaskHandler` ir paieškos indekso `is_complete`.
- VU SA išimtis: `Meeting::requiresStudentPerspective()`.
- Vienas pagrindinis balsavimas: `AgendaItemController::ensureSingleMainVote()`,
  `VoteController::destroy()`.
- Reikšmių pavadinimai: `VoteValue` (serveris), `useAgendaItemStyling.ts` ir sąrašo filtrų
  `FACET_VALUE_LABELS` – visur tie patys.
- Senas redagavimo adresas `/mano/agendaItems/{id}/edit` nukreipia į klausimo puslapį.
