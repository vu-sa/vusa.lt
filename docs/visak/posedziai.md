---
doc_status: reviewed
title: Posėdžiai
area: meetings
models: [Meeting]
last_reviewed: 2026-11-01
tests:
  - tests/Feature/Search/ListFreshnessTest.php
  - resources/js/Components/Meetings/__tests__/MeetingCollectionRow.component.test.ts
  - resources/js/Components/Meetings/__tests__/MeetingPreview.component.test.ts
  - tests/Feature/Permissions/MeetingPermissionsTest.php
  - tests/Feature/Admin/Search/SearchVisibilityParityTest.php
  - resources/js/Pages/Admin/Representation/__tests__/IndexMeeting.component.test.ts
  - tests/Feature/Meetings/AgendaItemPrivacyTest.php
  - tests/Feature/Admin/Calendar/MeetingControllerTest.php
  - tests/Feature/Admin/Calendar/AgendaItemControllerTest.php
  - tests/Feature/Admin/Management/InstitutionCheckInTest.php
  - tests/Feature/Meetings/MeetingCalendarEventTest.php
  - tests/Feature/Meetings/MeetingDocumentTest.php
  - tests/Feature/Admin/Resources/DocumentPickTest.php
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

## Susitarimai {#susitarimai}

1. Apie posėdį rekomenduojama pranešti **per 3 dienas** nuo tada, kai sužinoma jo data. Jei iki posėdžio liko mažiau
   nei 3 dienos, pranešama nedelsiant.
   - Kai žinoma darbotvarkė, įkeliami jos klausimai. Jei ji atnaujinama, atnaujinama ir platformoje.
2. Po posėdžio užfiksuojami priimti sprendimai:
   - įkeliamas sekretoriaus atsiųstas **protokolas** ir parengta **ataskaita**;
   - pažymima, ar klausimas patvirtintas, kaip balsavo studentai ir ar sprendimas palankus studentams.
3. **Elektroninis posėdis** (balsavimas el. paštu) fiksuojamas taip pat, kaip ir kiti posėdžiai.
4. Jei institucijoje yra keli atstovai, rekomenduojama susitarti, kuris fiksuoja posėdį ir kelia dokumentus.

## Kaip tai veikia

### Posėdžio tipas ir pavadinimas

Posėdis būna **Gyvas posėdis**, **Nuotolinis posėdis**, **Sprendimas el. paštu** arba be tipo
(„Kita“). Pavadinimas sudaromas automatiškai iš datos ir laiko, pvz., „2026 kovo 05 d. 16.30 val.
posėdis“. Sprendimui el. paštu laikas nerodomas. Pakeitus datą ar tipą, pavadinimas atnaujinamas.

### Matomumas {#matomumas}

Posėdžio ir jo darbotvarkės klausimų puslapiuose matomumas rodomas taip:

| Matomumas | Kada | Kas rodoma vusa.lt |
|---|---|---|
| **Matoma vusa.lt** | Viena posėdžio institucijų yra tipo, kurio posėdžiai [posėdžių nustatymuose](/sistema/nustatymai) pažymėti viešais (žr. [Institucijos](/visak/institucijos#viesumas)) | Posėdžio puslapis: data, institucija, būsena, darbotvarkė, atstovai ir paskelbti dokumentai |
| **Matoma renginio puslapyje** | Posėdis neviešas, bet paskelbtas [kalendoriuje](/svetaine/kalendorius) | Renginio puslapyje – posėdžio darbotvarkė ir paskelbti dokumentai |
| **Rodoma tik ViSAK · renginys – juodraštis** | Posėdis susietas su kalendoriaus renginiu, bet renginys dar nepaskelbtas | Nieko, kol renginys nepaskelbtas |
| **Rodoma tik ViSAK** | Nė viena iš aukščiau nurodytų sąlygų | Nieko |

Abiem viešais atvejais nuoroda veda į atitinkamą viešą puslapį.

Viešą posėdį gali atidaryti ir nariai, kurie jo tvarkyti negali: jie mato darbotvarkę, bet ne
failus, užduotis, komentarus ir atstovų pastabas.

Šalia matomumo paspaudus **Kas rodoma?**, pateikiama informacija, kas iš posėdžio rodoma vusa.lt, o kas –
niekada. Skirtuko **Failai** failai viešai nerodomi niekada (žr.
[Įrašų failai](/visak/failai#viesumas)).

<ChangelogNote date="2026-10-02" title="Kas rodoma vusa.lt?">

Iki v3 posėdžio puslapis rodė tik „Matoma vusa.lt“ ar „Rodoma tik ViSAK“, nepaaiškindamas, kas
konkrečiai skelbiama.

</ChangelogNote>

<ChangelogNote date="2026-10-26" title="Matomumas per kalendorių">

Iki 2026-10-26 kalendoriuje paskelbtas posėdis buvo žymimas „Rodoma tik ViSAK“, nors jo darbotvarkė
rodoma renginio puslapyje.

</ChangelogNote>

### Bendri posėdžiai

Posėdis gali priklausyti kelioms institucijoms (pvz., bendras dviejų komitetų posėdis). Institucija
pridedama posėdžio meniu. Tą pačią instituciją pridėti dar kartą, taip pat pašalinti paskutinę
instituciją negalima.

### Užpildymo būsena {#busena}

Posėdžio būsena rodo, ar jo darbotvarkė užpildyta:

| Būsena | Kada |
|---|---|
| **Nėra darbotvarkės** | Posėdis neturi nė vieno klausimo |
| **Neužpildyta** | Bent vienam klausimui trūksta tipo arba balsavimo informacijos |
| **Užpildyta** | Visi klausimai užpildyti |

Kada punktas laikomas užpildytu, aprašyta puslapyje
[Darbotvarkės klausimai](/visak/darbotvarkes-klausimai#uzpildyta). Ta pati taisyklė taikoma
būsenai, užpildymo užduočiai ir darbotvarkės klausimų sąrašo filtrui **Užpildymo būsena**.

Po būsena rodoma **Nepilnų punktų: N** su nuorodomis į kiekvieną nepilną punktą.

### Po posėdžio: protokolas ir ataskaita {#protokolas}

Laukas **Po posėdžio** rodo, ar įkelti posėdžio **protokolas** ir **ataskaita** (SharePoint failai
su tipu „Protokolai“ arba „Ataskaitos“). Jie įkeliami skirtuke **Failai** (žr. [Įrašų failai](/visak/failai)).

### Skirtukai

| Skirtukas | Kas jame |
|---|---|
| **Darbotvarkė** | Klausimai su būsena; čia pridedami ir perrikiuojami klausimai, o mygtukas **Redaguoti** prie klausimo atveria jo redagavimo langą neišeinant iš posėdžio |
| **Dokumentai** | Tik VU SA darinių posėdžiams: su posėdžiu susieti dokumentų naršyklės dokumentai (nutarimai, protokolai) |
| **Failai** | Posėdžio darbo failai SharePoint; viešai nerodomi (žr. [Įrašų failai](/visak/failai)) |
| **Užduotys** | Posėdžio užduotys; skaičius rodo neatliktas |

## Veiksmai

### Fiksuoti posėdį {#fiksuoti}

Posėdį užfiksuoti gali tie, kas eina atstovavimo pareigybę institucijoje. Paspausk **Veiksmai →
Fiksuoti posėdį** (arba institucijos puslapyje **Fiksuoti veiklą**) ir veiksmų lange:

1. pasirink instituciją (jei langas atvertas iš institucijos, ji jau pasirinkta). Studentų atstovas
   renkasi iš institucijų, kuriose eina pareigas; koordinatorius gali ieškoti ir kitų savo padalinio
   institucijų;
2. pasirink posėdžio tipą;
3. nurodyk datą ir laiką – siūlomos šiandiena, vakar ir įprasta institucijos posėdžių diena;
   sprendimui el. paštu laikas nustatomas 23:59;
4. įvesk darbotvarkės punktus (po vieną eilutę), pasirink įvesti juos vėliau arba praleisk;
5. VU SA darinio posėdį gali iš karto **paskelbti kalendoriuje**.

Kiti **Veiksmai** veiksmai:

- **Posėdžio nebuvo** – pranešti, kad institucija nesirinko nuo paskutinio posėdžio ar tavo
  kadencijos pradžios ir (ar) kurį laiką nesirinks (žr.
  [Institucijos](/visak/institucijos#posedzio-nebuvo)). Jei vėliau užregistruojamas posėdis tame
  laikotarpyje, pranešimas sutrumpinamas.
- **Užbaigti posėdį** – papildyti jau užregistruotą praėjusį posėdį. Pirmiausia siūlomi posėdžiai
  be darbotvarkės; būsimi posėdžiai nesiūlomi.

### Darbotvarkės punktų pridėjimas

Posėdžio puslapyje **Pridėti punktų** siūlo tris būdus: **Po vieną**, **Įklijuoti** (viena eilutė –
vienas punktas; numeracija ir laikas, pvz., „10.00–10.30“, atpažįstami) arba **Iš ankstesnio
posėdžio** (paimami ankstesnio posėdžio punktai, kuriuos gali pataisyti). Nauji punktai pridedami
darbotvarkės gale. Kaip pildyti punktus, aprašyta puslapyje
[Darbotvarkės klausimai](/visak/darbotvarkes-klausimai).

### Posėdžio keitimas {#keitimas}

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
  Atsiejus dokumentas lieka dokumentų naršyklėje. Mygtukas **Laukia SharePoint** atveria
  langą su dar nepaskelbtais to padalinio failais (pirmiausia – posėdžio dienos); kitų galima
  ieškoti pagal pavadinimą. Pažymėtus failus **Paskelbti ir susieti** paskelbia vusa.lt ir susieja
  su posėdžiu. Jau su kitu posėdžiu susietas dokumentas nesusiejamas – pirma jį atsiek ten. Mygtuką
  mato tie, kas gali redaguoti posėdį ir tvarkyti padalinio dokumentus. **Pasirinkti iš SharePoint**
  atveria SharePoint langą: pasirinkti archyvo failai susiejami su posėdžiu, o dar neperžiūrėti
  ir paskelbiami. Failas turi priklausyti posėdžio institucijos padaliniui (padalinys nurodomas
  SharePoint).
- **Ištrinti** – posėdis patenka į [šiukšlinę](/pagrindai/platforma#siuksline) kartu su
  darbotvarke, balsavimais ir pastabomis, o jo užduotys pašalinamos. Atkūrus grįžta viskas, o
  neužbaigta darbotvarkės užduotis sukuriama iš naujo. Posėdis, sukurtas mažiau nei prieš parą ir
  dar tuščias (be darbotvarkės, failų, dokumentų, komentarų ir kalendoriaus įrašo), ištrinamas visam
  laikui – patvirtinimo lange tai parašyta.

<ChangelogNote date="2026-10-25" title="Ką tik sukurtas tuščias posėdis ištrinamas visam laikui">

Anksčiau toks posėdis patekdavo į šiukšlinę, o atkūrus posėdį jo užduotys negrįždavo.

</ChangelogNote>

### Posėdžių sąrašas

Sąraše (`/mano/meetings`) filtruojama pagal metus, būseną, balsavimo atitikimą, institucijos tipą
ir padalinį. Pirmą kartą rodomi tavo padalinių posėdžiai. Posėdžiai, kuriuos ką tik keitei,
rodomi viršuje. Išsaugojus balsavimą ar pakeitus darbotvarkę, sąrašas ir viešas posėdžio puslapis
atsinaujina iš karto.

Lentelėje yra stulpeliai **Data**, **Institucija**, **Būsena**, **Balsavimas** ir **Klausimai**.
**Sukurta** ir **Atnaujinta** paslėpti, juos įjungia meniu **Stulpeliai**. Rikiuoti galima paspaudus
datos, klausimų ar sukūrimo ir atnaujinimo stulpelio antraštę.

Eilutėje ir lentelėje yra **Atidaryti** ir meniu **⋯** su kitais veiksmais. Telefone visi veiksmai yra
meniu **⋯**, o pavadinimas atveria posėdį. Peržiūroje rezultatas rodomas tik kortelėje dešinėje.

#### Kaip praėjo posėdis {#kaip-praejo}

Sąrašas ir institucijos paskutiniai posėdžiai vienodai parodo, kaip praėjo posėdis:

| Žyma ar skaičius | Ką reiškia |
|---|---|
| **Neužpildyta**, **Nėra darbotvarkės** | [Užpildymo būsena](#busena). Sąrašo eilutėje užpildytas posėdis jos nerodo, stulpelyje **Būsena** ji matyti visada |
| **2 iš 3 balsavimų sutapo** | Kiek sprendimų sutapo su studentų pozicija. Žalia – sutapo visi, geltona – dalis, raudona – nė vienas. Bendru sutarimu priimtas sprendimas laikomas sutapusiu |
| **už**, **prieš**, **susilaikė** | Sprendimai VU SA organuose, kur atskiros studentų pozicijos nėra |
| **aptarimai**, **atidėti** | Tokio tipo klausimų skaičius |
| **be rezultato** | Klausimai, kurių balsavimo rezultatas neįrašytas |

Skaičiuojami darbotvarkės klausimai, o ne atskiri balsavimai: jei klausimui pažymėtas pagrindinis
balsavimas, vertinamas jis, kitaip – visi klausimo balsavimai. Pertraukos ir išbraukti klausimai
neskaičiuojami. Sąraše skaičiuojami tik viešai matomi klausimai, o jei posėdis turi klausimų, rodomų
tik ViSAK, užpildymo būsena jame nerodoma. Institucijos paskutiniuose posėdžiuose įskaičiuojami ir
tie, kuriuos tau leidžiama matyti.

<ChangelogNote date="2026-11-01" title="Kaip praėjo posėdis">

Sąrašuose atsirado posėdžio rezultatas, o lentelėje – stulpeliai **Balsavimas** ir **Klausimai**.

</ChangelogNote>

Atskiras klausimas gali būti pažymėtas **Rodoma tik ViSAK**, net jei posėdis viešas. Viešai lieka
jo numeris ir nebūtinas viešas pavadinimas; matomumo žyma nerodoma. [Klausimo matomumas](/visak/darbotvarkes-klausimai#matomumas).

## Kas ką gali {#teises}

**Institucijos narys posėdžio metu** – žmogus, kuris posėdžio dieną ėjo pareigas bent vienoje jo
institucijoje. Buvęs narys mato ir gali pildyti savo kadencijos posėdžius, bet ne vėlesnius. Jo
pakeitimai matomi posėdžio istorijoje.

Einamų pareigybių institucijų posėdžius galima matyti ir iš kitų laikotarpių, tačiau tai savaime
neleidžia jų redaguoti. Atsakomybės redagavimo apimtis **Savo institucijų, visų kadencijų** leidžia keisti visus
teisę suteikiančių pareigybių institucijų posėdžius, nepriklausomai nuo posėdžio datos.
Posėdžių kūrimas ir perkėlimas į šiukšlinę reikalauja atskirų teisių.

<ChangelogNote date="2026-10-21" title="Paieška rodo ir pareigų laikotarpio posėdžius">

Automatiškai prieinami posėdžiai randami sąraše ir paieškoje be papildomos atsakomybės, taip pat
ir pasibaigus pareigoms. Kiti institucijos posėdžiai ir ankstesnės darbotvarkės siūlomi tik tada,
kai narys gali juos matyti. Turintiems teisę atkurti posėdį jis rodomas šiukšlinėje; kūrimo teisės tam nereikia.

</ChangelogNote>

| Veiksmas | Bet kuris narys | Institucijos narys posėdžio metu | Studentų atstovas | Studentų atstovų koordinatorius | Komunikacijos koordinatorius | Centrinio biuro studentų atstovų koordinatorius |
|---|---|---|---|---|---|---|
| Matyti viešą posėdį | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Matyti visą posėdžio puslapį | – | ✓ | ✓, savo institucijų | ✓, savo padalinio | ✓, savo padalinio | ✓ |
| Fiksuoti posėdį | – | – | ✓, savo institucijų | ✓, savo padalinio | ✓, savo padalinio | ✓ |
| Keisti posėdį, perrikiuoti punktus | – | ✓ | ✓, savo institucijų | ✓, savo padalinio | ✓, savo padalinio | ✓ |
| Pildyti darbotvarkės punktus ir balsavimus | – | ✓ | ✓, savo institucijų | ✓, savo padalinio | ✓, savo padalinio | ✓ |
| Ištrinti ir atkurti | – | – | ✓, savo institucijų | ✓, savo institucijų | ✓, savo padalinio | ✓ |

Institucija, susieta su tavo institucija [ryšiu](/sistema/rysiai), leidžia matyti ir jos
posėdžius (vienpusis ryšys – tik kryptimi nuo tavo institucijos). Paskirtas sekretorius taip pat
mato prižiūrimos institucijos posėdžius; tai savaime nesuteikia redagavimo teisės. Negrįžtamam
trynimui reikia atskiros teisės; įprastos atstovų ir koordinatorių atsakomybės jos neturi.

<ChangelogNote date="2026-10-20" title="Darbotvarkę pildo ir buvę nariai">

Anksčiau darbotvarkės punktus ir balsavimus galėjo pildyti tik tie, kurie pareigas ėjo pildymo
dieną. Dabar juos pildo ir posėdžio metu buvę institucijos nariai.

</ChangelogNote>

## Pranešimai ir automatizavimas {#pranesimai}

| Kada | Kas gauna | Ką |
|---|---|---|
| Užregistruojamas posėdis be darbotvarkės | Atstovai (arba kadencijos sekretoriai) | Užduotį **„Sukurti posėdžio darbotvarkės klausimus“**, terminas – 3 dienos po posėdžio |
| Posėdis įgyja darbotvarkę | Tie patys | Užduotį **„Užpildyti darbotvarkės klausimų informaciją“**, terminas – 7 dienos po posėdžio; darbotvarkės kūrimo užduotis užbaigiama |
| Pildomi punktai | – | Užduoties eiga (pvz., „1 iš 2“) atnaujinama; užpildžius visus, užduotis užbaigiama. Jei punktas vėl tampa nepilnas – užduotis atveriama |
| Posėdis perkeliamas į šiukšlinę / atkuriamas | Tie patys | Užduotys pašalinamos; atkūrus vėl sukuriama darbotvarkės kūrimo arba pildymo užduotis (jei darbotvarkė jau užpildyta – nekuriama) |
| Užregistruojamas posėdis | Koordinatoriai ir institucijos sekėjai | Pranešimą apie naują posėdį |
| Užpildoma visa darbotvarkė | Koordinatoriai ir sekėjai | Pranešimą, kad darbotvarkė užpildyta |
| Prieš posėdį | Posėdžio dieną pareigas einantys nariai ir sekretoriai | Priminimą (numatytai **prieš 24 ir 1 valandą**) |

- Užduotys skiriamos atstovams, kurie ėjo pareigas posėdžio dieną, o jei kadencijai paskirti
  [sekretoriai](/visak/institucijos#sekretoriai) – tik jiems. Jei atstovų nėra, užduotys nekuriamos.
- Priminimo laiką (24, 12 ar 1 val. prieš) galima pakeisti [pranešimų nustatymuose](/mano/pranesimai).
  Apie ištrintus posėdžius nepriminama.
- Nebesekant institucijos arba nebegalint matyti posėdžio, sekėjams skirtų pranešimų negaunama.
  Pranešimai dėl pareigų siunčiami atskirai. Užduotį gavęs žmogus apie tą patį posėdį atskiro
  pranešimo negauna.
- Užregistravus posėdį, institucijos užduotis dėl periodiškumo užbaigiama.

Plačiau apie užduotis – [Užduotys](/mano/uzduotys).

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
  peržiūra tikrinama pagal `meetings.read.{*|padalinys}`, redagavimas – pagal
  `meetings.update.{*|own|padalinys}`; `delete` ir `restore` – tik
  `meetings.delete.{*|own|padalinys}` (narystė posėdžio metu čia nepadeda).
- `.own` – visi teisę suteikiančios pareigybės institucijos posėdžiai. Atkūrimas instituciją tikrina
  tiesiogiai, nes `Duty::meetings()` neįtraukia ištrintų posėdžių. `MeetingAccessService` suderina
  bazinę prieigą, paieškos filtrus, kitų posėdžių nuorodas ir šiukšlinės užklausą.
- `meetings.read.own` atsisakyta: bazinė peržiūra galioja be šio leidimo, taip pat paieškoje.
- Rolės: Studentų atstovas – `meetings.create|update|delete.own`; Studentų atstovų
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
- Rezultatas: `Meeting::outcomeSummary()` (`VoteStatisticsCalculator::outcomeSummary()`); paieškos
  dokumentus po balsavimo ar klausimo pakeitimo atnaujina `SyncMeetingOutcomeIndex`.
- Užduotys: `MeetingTaskSubscriber` (`MeetingFullyCreated`, punktų sukūrimas, išsaugojimas,
  ištrynimas) → `AgendaCreationTaskHandler`, `AgendaCompletionTaskHandler`.
- Priminimai: `notifications:meeting-reminders` kas 30 min., ±30 min. langas; valandos –
  `meeting_reminder_hours` naudotojo nustatymuose.
- Senas redagavimo adresas `/mano/meetings/{id}/edit` nukreipia į posėdžio puslapį.
