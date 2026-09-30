---
doc_status: reviewed
title: Posėdžiai
area: meetings
models: [Meeting]
last_reviewed: 2026-09-27
tests:
  - tests/Feature/Admin/Calendar/MeetingControllerTest.php
  - tests/Feature/Admin/Management/InstitutionCheckInTest.php
  - tests/Feature/Meetings/MeetingCalendarEventTest.php
  - tests/Feature/Meetings/MeetingDocumentTest.php
  - tests/Feature/Meetings/MeetingStudentPerspectiveTest.php
  - tests/Feature/Meetings/MeetingTranslationsTest.php
  - tests/Feature/Public/PublicMeetingVisibilityTest.php
  - tests/Feature/SoftDelete/DestructiveDeleteHooksTest.php
  - tests/Feature/Api/Admin/ActionWindowApiControllerTest.php
  - tests/Feature/Tasks/Subscribers/MeetingTaskSubscriberTest.php
  - tests/Feature/Tasks/InstitutionSecretaryMailScopeTest.php
  - tests/Feature/Notifications/SendMeetingRemindersCommandTest.php
  - tests/Feature/Notifications/NotificationFiringTest.php
  - tests/Feature/Notifications/InactiveMemberNotificationTest.php
  - tests/Unit/Services/MeetingCompletionServiceTest.php
  - resources/js/Pages/Admin/Representation/__tests__/ShowMeeting.component.test.ts
  - resources/js/Components/Meetings/__tests__/MeetingAgendaList.component.test.ts
  - resources/js/Components/Meetings/__tests__/AddAgendaItemsSheet.component.test.ts
  - resources/js/Components/Meetings/__tests__/MeetingDocumentsPanel.component.test.ts
  - resources/js/Components/ActionWindow/__tests__/ActionWindow.component.test.ts
  - resources/js/Components/ActionWindow/__tests__/MeetingWhenScreen.component.test.ts
  - resources/js/Components/ActionWindow/__tests__/MeetingAgendaScreen.component.test.ts
  - resources/js/Components/ActionWindow/__tests__/MeetingReviewScreen.component.test.ts
  - tests/Feature/Permissions/StudentRepresentativeRoleTest.php
  - tests/Browser/ActionWindowTest.php
  - tests/Browser/VisakRecordPagesTest.php
---

# Posėdžiai

Posėdis – bet koks institucijos susitikimas, kuriame sprendžiami klausimai: gyvas ar nuotolinis
posėdis, sprendimas el. paštu ar kitas susitikimas. Platformoje studentų atstovai praneša apie
posėdžius, įkelia darbotvarkę, protokolą ir ataskaitą, pažymi balsavimų rezultatus ir nurodo, ar
sprendimas buvo palankus studentams.

Posėdžių sąrašas pasiekiamas adresu `/mano/meetings`, o kiekvienas posėdis turi savo puslapį
`/mano/meetings/{id}`.

<DocScreenshot name="meeting-record" alt="Posėdžio puslapis: būsena „Neužpildyta“, matomumas, laikas, institucija, atstovai, protokolas ir ataskaita, nepilnų punktų sąrašas ir darbotvarkė" caption="Praėjęs posėdis: vienam darbotvarkės punktui dar trūksta informacijos." />

## Kaip tai veikia

### Posėdžio tipas ir pavadinimas

Posėdis būna **Gyvas posėdis**, **Nuotolinis posėdis**, **Sprendimas el. paštu** arba be tipo
(„Kita“). Pavadinimas sudaromas automatiškai iš datos ir laiko, pvz., „2026 kovo 05 d. 16.30 val.
posėdis“. Sprendimui el. paštu laikas nerodomas. Pakeitus datą ar tipą, pavadinimas atnaujinamas.

### Matomumas {#matomumas}

Posėdžio puslapyje rodoma **Matoma vusa.lt** (su nuoroda į viešą puslapį) arba **Tik viduje**.
Posėdis viešas, jei viena jo institucijų yra tipo, kurio posėdžiai
[posėdžių nustatymuose](/sistema/nustatymai) pažymėti viešais (žr.
[Institucijos](/visak/institucijos#viesumas)). Viešame puslapyje rodomi tik paskelbti posėdžio
dokumentai.

Viešą posėdį gali atidaryti ir nariai, kurie jo tvarkyti negali: jie mato darbotvarkę, bet ne
failus, užduotis, komentarus ir atstovų pastabas.

### Bendri posėdžiai

Posėdis gali priklausyti kelioms institucijoms (pvz., bendras dviejų komitetų posėdis). Institucija
pridedama posėdžio meniu. Tą pačią instituciją pridėti dar kartą, taip pat pašalinti paskutinę
instituciją negalima.

### Užpildymo būsena {#busena}

Posėdžio būsena rodo, ar jo darbotvarkė užpildyta:

| Būsena | Kada |
|---|---|
| **Nėra darbotvarkės** | Posėdis neturi nė vieno punkto |
| **Neužpildyta** | Bent vienam punktui trūksta tipo arba balsavimo informacijos |
| **Užpildyta** | Visi punktai užpildyti |

Kada punktas laikomas užpildytu, aprašyta puslapyje
[Darbotvarkės klausimai](/visak/darbotvarkes-klausimai#uzpildyta). Ta pati taisyklė taikoma
būsenai, užpildymo užduočiai ir darbotvarkės klausimų sąrašo filtrui **Užpildymo būsena**.

Po būsena rodoma **Nepilnų punktų: N** su nuorodomis į kiekvieną nepilną punktą.

### Po posėdžio: protokolas ir ataskaita {#protokolas}

Laukas **Po posėdžio** rodo, ar įkelti posėdžio **protokolas** ir **ataskaita** (SharePoint failai
su tipu „Protokolai“ arba „Ataskaitos“). Juos įkelk skirtuke **Failai**.

### Skirtukai

| Skirtukas | Kas jame |
|---|---|
| **Darbotvarkė** | Punktai su būsena; čia pridedami ir perrikiuojami punktai |
| **Dokumentai** | Tik VU SA darinių posėdžiams: su posėdžiu susieti dokumentų naršyklės dokumentai (nutarimai, protokolai) |
| **Failai** | Posėdžio SharePoint failai |
| **Užduotys** | Posėdžio užduotys; skaičius rodo neatliktas |

## Veiksmai

### Fiksuoti posėdį {#fiksuoti}

Posėdį užfiksuoti gali tie, kas eina atstovavimo pareigybę institucijoje. Paspausk **+ Sukurti →
Fiksuoti posėdį** (arba institucijos puslapyje **Fiksuoti veiklą**) ir veiksmų lange:

1. pasirink instituciją (jei langas atvertas iš institucijos, ji jau pasirinkta). Studentų atstovas
   renkasi iš institucijų, kuriose eina pareigas; koordinatorius gali ieškoti ir kitų savo padalinio
   institucijų;
2. pasirink posėdžio tipą;
3. nurodyk datą ir laiką – siūlomos šiandiena, vakar ir įprasta institucijos posėdžių diena;
   sprendimui el. paštu laikas nustatomas 23:59;
4. įvesk darbotvarkės punktus (po vieną eilutę), pasirink įvesti juos vėliau arba praleisk;
5. VU SA darinio posėdį gali iš karto **paskelbti kalendoriuje**.

Kiti **+ Sukurti** veiksmai:

- **Posėdžio nebuvo** – pranešti, kad institucija kurį laiką nesirinks (žr.
  [Institucijos](/visak/institucijos#posedzio-nebuvo)). Jei vėliau užregistruoji posėdį tame
  laikotarpyje, pranešimas sutrumpinamas.
- **Užbaigti posėdį** – papildyti jau užregistruotą praėjusį posėdį. Pirmiausia siūlomi posėdžiai
  be darbotvarkės; būsimi posėdžiai nesiūlomi.

### Darbotvarkės punktų pridėjimas

Posėdžio puslapyje **Pridėti punktų** siūlo tris būdus: **Po vieną**, **Įklijuoti** (viena eilutė –
vienas punktas; numeracija ir laikas, pvz., „10.00–10.30“, atpažįstami) arba **Iš ankstesnio
posėdžio** (paimami ankstesnio posėdžio punktai, kuriuos gali pataisyti). Nauji punktai pridedami
darbotvarkės gale. Kaip pildyti punktus, aprašyta puslapyje
[Darbotvarkės klausimai](/visak/darbotvarkes-klausimai).

### Posėdžio keitimas

- **Redaguoti posėdį** – šoninėje formoje pakeisti datą, tipą ir aprašymą. Jei uždarai formą
  neišsaugojęs pakeitimų, sistema paprašo patvirtinti. Aprašymą gali įrašyti lietuviškai ir angliškai.
- **Kiti institucijos posėdžiai** – rodyklėmis pereiti prie ankstesnio ar kito tos pačios
  institucijos posėdžio.
- **Paskelbti kalendoriuje** (tik VU SA dariniams) – sukuriamas kalendoriaus įrašo juodraštis su
  posėdžio data ir padaliniu arba susiejamas jau esamas įrašas. Posėdį kalendoriuje galima
  paskelbti tik kartą. Pakeitus posėdžio laiką, įrašas pasislenka kartu; atsiejus – įrašas lieka.
  Paskelbtas renginys rodo posėdžio darbotvarkę, bet pats posėdis viešu netampa (žr.
  [Matomumas](#matomumas)).
- **Dokumentai** – susieti institucijos (ar kitos to paties padalinio institucijos) dokumentus.
  Atsiejus dokumentas lieka dokumentų naršyklėje.
- **Ištrinti** – posėdis patenka į [šiukšlinę](/pagrindai/platforma#siuksline) kartu su
  darbotvarke, balsavimais ir pastabomis. Atkūrus grįžta viskas.

### Posėdžių sąrašas

Sąraše (`/mano/meetings`) filtruojama pagal metus, būseną, balsavimo atitikimą, institucijos tipą
ir padalinį. Pirmą kartą rodomi tavo padalinių posėdžiai. Posėdžiai, kuriuos ką tik keitei,
rodomi viršuje.

## Kas ką gali {#teises}

**Institucijos narys posėdžio metu** – žmogus, kuris posėdžio dieną ėjo pareigas bent vienoje jo
institucijoje. Buvęs narys mato savo kadencijos posėdžius, bet ne vėlesnius.

| Veiksmas | Bet kuris narys | Institucijos narys posėdžio metu | Studentų atstovas | Studentų atstovų koordinatorius | Komunikacijos koordinatorius | Centrinio biuro studentų atstovų koordinatorius |
|---|---|---|---|---|---|---|
| Matyti viešą posėdį | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Matyti visą posėdžio puslapį | – | ✓ | ✓, savo institucijų | ✓, savo padalinio | ✓, savo padalinio | ✓ |
| Fiksuoti posėdį | – | – | ✓, savo institucijų | ✓, savo padalinio | ✓, savo padalinio | ✓ |
| Keisti posėdį, perrikiuoti punktus | – | ✓ | ✓, savo institucijų | ✓, savo padalinio | ✓, savo padalinio | ✓ |
| Pildyti darbotvarkės punktus | – | – | ✓, savo institucijų | ✓, savo padalinio | ✓, savo padalinio | ✓ |
| Ištrinti ir atkurti | – | – | ✓, savo institucijų | ✓, savo institucijų | ✓, savo padalinio | ✓ |

Institucija, susieta su tavo institucija [ryšiu](/sistema/rysiai), leidžia matyti ir jos
posėdžius (vienpusis ryšys – tik kryptimi nuo tavo institucijos). Galutinai ištrinti posėdį gali
tik super administratorius.

## Pranešimai ir automatizavimas {#pranesimai}

| Kada | Kas gauna | Ką |
|---|---|---|
| Užregistruojamas posėdis be darbotvarkės | Atstovai (arba kadencijos sekretoriai) | Užduotį **„Sukurti posėdžio darbotvarkės klausimus“**, terminas – 3 dienos po posėdžio |
| Posėdis įgyja darbotvarkę | Tie patys | Užduotį **„Užpildyti darbotvarkės klausimų informaciją“**, terminas – 7 dienos po posėdžio; darbotvarkės kūrimo užduotis užbaigiama |
| Pildomi punktai | – | Užduoties eiga (pvz., „1 iš 2“) atnaujinama; užpildžius visus, užduotis užbaigiama. Jei punktas vėl tampa nepilnas – užduotis atveriama |
| Užregistruojamas posėdis | Koordinatoriai ir institucijos sekėjai | Pranešimą apie naują posėdį |
| Užpildoma visa darbotvarkė | Koordinatoriai ir sekėjai | Pranešimą, kad darbotvarkė užpildyta |
| Prieš posėdį | Posėdžio dieną pareigas einantys nariai ir sekretoriai | Priminimą (numatytai **prieš 24 ir 1 valandą**) |

- Užduotys skiriamos atstovams, kurie ėjo pareigas posėdžio dieną, o jei kadencijai paskirti
  [sekretoriai](/visak/institucijos#sekretoriai) – tik jiems. Jei atstovų nėra, užduotys nekuriamos.
- Priminimo laiką (24, 12 ar 1 val. prieš) keisk [pranešimų nustatymuose](/mano/pranesimai).
  Apie ištrintus posėdžius nepriminama.
- Sekėjas, nutildęs instituciją arba nebegalintis matyti posėdžio, pranešimų negauna. Užduotį
  gavęs žmogus apie tą patį posėdį atskiro pranešimo negauna.
- Užregistravus posėdį, institucijos užduotis dėl periodiškumo užbaigiama.

Plačiau apie užduotis – [Užduotys](/mano/uzduotys).

## Susitarimai {#susitarimai}

::: tip Studentų atstovų atsakomybės
1. Apie posėdį pranešk **per 3 dienas** nuo tada, kai sužinai jo datą. Jei iki posėdžio liko mažiau
   nei 3 dienos, pranešk nedelsdamas.
   - Kai žinoma darbotvarkė, įkelk jos klausimus. Jei ji atnaujinama, atnaujink ir platformoje.
2. Po posėdžio užfiksuok priimtus sprendimus:
   - įkelk sekretoriaus atsiųstą **protokolą** ir savo parengtą **ataskaitą**;
   - pažymėk, ar klausimas patvirtintas, kaip balsavo studentai ir ar sprendimas palankus studentams.
3. **Elektroninis posėdis** (balsavimas el. paštu) fiksuojamas taip pat, kaip ir kiti posėdžiai.
4. Jei institucijoje keli atstovai, susitarkite, kuris fiksuoja posėdį ir kelia dokumentus.
:::

::: details Protokolas ar ataskaita?
**Posėdžio protokolas** – oficialus sekretoriaus parengtas dokumentas su aptartais klausimais ir
nutarimais. Paprastai atsiunčiamas per dvi savaites po posėdžio.
**Studentų atstovo ataskaita** – tie patys klausimai ir nutarimai, papildyti studentų perspektyva ir
esminėmis narių mintimis. Jei protokolas neatsiunčiamas, ataskaita jį atstoja.
:::

## Techninė informacija {#technine-informacija}

### Teisės

- Posėdžių teises sprendžia `MeetingPolicy`. `viewSummary` – posėdis viešas arba leidžiamas `view`.
  `view` ir `update` leidžiami ir **nariui posėdžio metu** (`Meeting::hadMemberAtTheTime()`), kitaip
  tikrinama `meetings.{read|update}.{*|own|padalinys}`; `delete` ir `restore` – tik
  `meetings.delete.{*|own|padalinys}` (narystė posėdžio metu čia nepadeda).
- `.own` – visi pareigybės institucijų posėdžiai (`Duty::meetings()`).
- Rolės: Studentų atstovas – `meetings.create|read|update|delete.own`; Studentų atstovų
  koordinatorius – `create|read|update.padalinys`, `delete.own`; Komunikacijos koordinatorius – visos
  `.padalinys`; Centrinio biuro studentų atstovų koordinatorius – visos `*`. `meetings.forceDelete`
  neturi nė viena rolė.
- Kurti: `MeetingPolicy::create()` – `meetings.create.padalinys` arba `.own`; institucija tikrinama
  `MeetingPolicy::createFor()`: `.padalinys` – bet kuri padalinio institucija, `.own` – tik tų
  pareigybių, kurios suteikia teisę, institucijos (`StoreMeetingRequest`).

### Kaip tai įgyvendinta

- Pavadinimas: `App\Support\MeetingTitle`; perskaičiuojamas `MeetingController::update()`.
- Viešumas: `Meeting::is_public` pagal `MeetingSettings::public_meeting_institution_type_ids`.
- Būsena ir trūkstami veiksmai: `MeetingCompletionService::calculate()` / `missingActions()`.
- Užduotys: `MeetingTaskSubscriber` (`MeetingFullyCreated`, punktų sukūrimas, išsaugojimas,
  ištrynimas) → `AgendaCreationTaskHandler`, `AgendaCompletionTaskHandler`.
- Priminimai: `notifications:meeting-reminders` kas 30 min., ±30 min. langas; valandos –
  `meeting_reminder_hours` naudotojo nustatymuose.
- Senas redagavimo adresas `/mano/meetings/{id}/edit` nukreipia į posėdžio puslapį.
