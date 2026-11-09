---
title: Mano kelias
doc_status: reviewed
last_reviewed: 2026-11-10
tests:
  - tests/Feature/Admin/Learning/ResolveLearningPathTest.php
  - tests/Feature/Admin/Learning/LearningRoutesTest.php
  - tests/Feature/Admin/Learning/LearningPoliciesTest.php
  - tests/Feature/Admin/Learning/LearningWritesTest.php
  - tests/Feature/Admin/Learning/LearningStepProgressControllerTest.php
  - tests/Feature/Admin/Learning/LearningLibraryTest.php
  - tests/Feature/Admin/Learning/LearningDocumentsTest.php
  - tests/Feature/Admin/Learning/LearningPeopleTest.php
  - tests/Feature/Admin/Learning/LearningPathPresenterTest.php
  - tests/Feature/Admin/Learning/SystemStepDetectorsTest.php
  - tests/Feature/Seeders/LearningPathPilotSeederTest.php
  - resources/js/Features/Admin/Learning/__tests__/LearningLibrary.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/BrowserNotificationSetup.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningTabs.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningDocuments.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningGlossarySteps.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningPathView.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningStepRow.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningStepForm.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningStepGlossaryEditor.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningRepList.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/HandoverEditor.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/HandoverNotes.component.test.ts
---

# Mano kelias

**Mano kelias** padeda pasiruošti pareigoms: ką verta perskaityti, ką padaryti ir kada. Kelias
sudaromas pagal tavo pareigas, todėl Studijų programos komiteto atstovas mato kitus žingsnius nei
Senato narys. Čia pat saugomos sąvokos ir pirmtakų perdavimo užrašai.

<ChangelogNote date="2026-11-10" title="Mano kelias">

Mano kelias pakeitė pareigybių ir institucijų tipų failus: vietoj failų sąrašo – žingsniai su
paaiškinimu, kam jų reikia, ir pažanga, kurią mato koordinatorius.

</ChangelogNote>

## Susitarimai

- **Šaltiniai laikomi Archyve.** Naujas dokumentas pirmiausia įkeliamas į SharePoint „Dokumentų
  sistemą“ (Archyvą), o žingsnis į jį nurodo. Vieši teisės aktai (pvz., įstatymai) susiejami nuoroda.
- **Kiekvienas žingsnis paaiškina, kam jis reikalingas.** Vienas–du sakiniai „Kodėl verta“ svarbesni už
  patį failą.
- **Esminių žingsnių nedaug.** Vienam sluoksniui užtenka kelių esminių žingsnių; visa kita –
  rekomenduojama arba papildoma.
- **Įžvalgos – autoriaus nuomonė.** Prie žingsnio rašoma, kas dokumente svarbiausia ir kur žiūrėti;
  įžvalga pasirašyta ir su data, nes kontekstas keičiasi.

## Kaip tai veikia

### Sluoksniai

Kiekvienam nariui kelias sudedamas iš sluoksnių:

| Sluoksnis | Kam rodoma |
|---|---|
| Platforma | Visiems nariams: paskyros, pranešimų ir programėlės nustatymai |
| Pagrindas | Visiems studentų atstovams ir jų koordinatoriams |
| Institucijos tipas | Tų tipų institucijų nariams, pvz., Studijų programos komitetui; paveldima iš aukštesnio tipo |
| Pareigos | Pagal pareigybės tipą |
| Padalinys | Padalinio atstovams; visada etape „Prieš pradedant“ |

Būsimos pareigos įskaitomos iš karto, todėl išrinktas, bet dar nepradėjęs atstovas jau mato etapą
„Prieš pradedant“.

### Etapai ir svarba

Žingsniai išdėstyti pagal kadencijos etapus: **Prieš pradedant**, **Pirmieji mėnesiai** (pirmos 60
dienų), **Visą kadenciją** ir **Kadencijos pabaiga** (paskutinės 45 dienos). Dabartinis etapas
pažymėtas „dabar“. Visi etapai ir jų žingsniai rodomi iš karto. Žingsnio kairėje esantis langelis
žymi pažangą; varnelę galima uždėti ir nuimti tiesiog eilutėje. Tik programėlės diegimo, naršyklės
pranešimų ir susipažinimo su koordinatoriumi ar institucijos kontaktais žingsniuose yra „Daugiau“.

Svarba nusako turinį, ne terminą: **Esminis** (būtina žinoti), **Rekomenduojamas**, **Papildomas**.
Esminio žingsnio praleisti negalima.

Žingsnio rūšys:

- **Žinios** – perskaityti dokumentą ar nuorodą.
- **Veiksmas** – ką nors padaryti, pvz., susipažinti su koordinatoriumi ar perskaityti praeitų
  posėdžių protokolus. Dalis veiksmų pasižymi patys pagal veiklą platformoje (įkelta nuotrauka,
  sekama institucija, užfiksuotas pirmas posėdis); tokie pažymėti „auto“.
- **Pasiruošimas posėdžiui** – kartojamas sąrašas po keliu, pvz., perskaityti medžiagą, pasiklausti
  kursiokų. Jame pažangos žymų nėra.

### Šaltiniai ir kalba

Žingsnis vienas abiem kalboms, bet anglų kalbai galima nurodyti kitą dokumentą ar nuorodą; jei jos nėra,
rodomas lietuviškas šaltinis. Šaltiniu gali būti ir Archyve paslėptas dokumentas – tada atstovai mato
žingsnį ir paaiškinimą, o vietoj nuorodos – „neviešas“. Dokumento pavadinimas atveria šaltinį naujame skirtuke. Veiksmo pavadinimas
rodomas kaip tekstas, o šalia jo – nuoroda veiksmui atlikti, jei tokia yra.

Jei šaltinio dokumentas Archyve atnaujinamas jau perskaičius, žingsnis pažymimas
„Atnaujinta – peržiūrėk“.

### Skirtukai

- **Mano kelias** – tavo žingsniai ir pažanga.
- **Dokumentai** – tavo kelio dokumentai vienoje vietoje, suskirstyti pagal sluoksnį: Pagrindas,
  institucijų tipai, Pareigos, Padalinys, Platforma. Prie dokumento galima pažymėti pažangą ir
  atverti susietų sąvokų paaiškinimus.
- **Sąvokos** – žodynėlis (SPK, akreditacija, kvorumas…). Po apibrėžimu „Kur sutinkama“ rodo
  susietus žingsnius: nuoroda atveria dokumentą arba žingsnio puslapį su įžvalgomis.
- **Perdavimas** – pirmtakų užrašai ir tavo užrašai įpėdiniui.
- **Mano atstovai** – tik studentų atstovų koordinatoriams.
- **Tvarkyti** – tik žingsnių kūrėjams ir redaktoriams. Čia rodomi visi žingsniai su paieška ir
  filtrais pagal sluoksnį, rūšį ir svarbą. Redaguojamuose žingsniuose matomos žymos: dokumentas
  nebegalioja, dokumentas atnaujintas po peržiūros arba nėra šaltinio.

<ChangelogNote date="2026-11-10" title="Dokumentai ir aiškesnis kelias">

Žingsniai rodomi be išskleidimo, dokumentai turi savo skirtuką, o sąvokos – nuorodas į susietus žingsnius.
Buvęs „Žinynas“ vadinamas „Tvarkyti“ ir pasiekiamas tik turinio kūrėjams bei redaktoriams.

</ChangelogNote>

### Perdavimo užrašai

Perdavimo užrašai pildomi penkiais laukais: **Kas vyksta institucijoje**, **Neužbaigti klausimai**,
**Žmonės**, **Naudingi dokumentai ir nuorodos**, **Patarimai**. Vienas pareigybės narys vienai
institucijos kadencijai (pvz., 2025–2026) turi vienus užrašus; juos galima pildyti iki 60 dienų po savo
pareigų pabaigos.

Skirtuke „Perdavimas“ kiekviena pareigybė rodoma kaip kadencijų laiko juosta – naujausia viršuje:
tavo dabartinės kadencijos užrašai redaguojami vietoje, ankstesni ir pirmtakų užrašai tik skaitomi.
Atvėrus pirmtakų užrašus, kelio žingsnis „Perskaityk pirmtako užrašus“ pasižymi pats.

## Veiksmai

### Eiti savo keliu

1. Atverk [Mano kelią](app:/mano/learning).
2. Perskaityk paaiškinimą po pavadinimu, atverk dokumentą arba atlik veiksmą.
3. Pažymėk langelį kairėje. Rekomenduojamą ar papildomą žingsnį gali **Praleisti** per **⋯** meniu.
4. Savo žymą panaikink nuimdamas varnelę. Automatiškai pažymėto langelio keisti negalima.
5. Dokumentus rask ir skirtuke [Dokumentai](app:/mano/learning/documents); įžvalgas atverk per
   jų skaičių prie žingsnio.

### Pridėti žingsnį

1. **+ Sukurti** → **Naujas mokymosi žingsnis** (arba **Tvarkyti** → **Naujas žingsnis**).
2. Pasirink rūšį, svarbą, įrašyk pavadinimą ir „Kodėl verta“.
3. Skiltyje **Kam** pasirink sluoksnį ir, jei reikia, institucijos ar pareigybės tipą arba padalinį.
4. Pasirink etapą ir šaltinį: dokumentą iš Archyvo arba nuorodą. Anglišką šaltinį nurodyk tik tada,
   kai jis skiriasi.
5. Išsaugok. Žingsnio puslapyje gali susieti sąvokas.

### Parašyti perdavimo užrašus

1. Atverk [Perdavimą](app:/mano/learning/handover).
2. Savo pareigybės laiko juostoje, prie dabartinės kadencijos, užpildyk bent vieną lauką ir spausk
   **Išsaugoti**.

## Kas ką gali {#teises}

| Kas | Ką gali |
|---|---|
| Kiekvienas narys | Matyti savo kelią, žymėti pažangą, skaityti savo dokumentus ir sąvokas, rašyti įžvalgas |
| Pareigas einantis ar neseniai baigęs narys | Rašyti savo kadencijos perdavimo užrašus; matyti pirmtakų užrašus |
| Studentų atstovų koordinatorius (atsakingas už institucijas) | Matyti „Mano atstovai“: savo atstovų pažangą ir jų kelius, jų perdavimo užrašus |
| Studentų atstovų koordinatorius | Atverti „Tvarkyti“, kurti ir redaguoti savo padalinio sluoksnio žingsnius |
| Centrinio biuro studentų atstovų koordinatorius | Kurti ir redaguoti visų sluoksnių žingsnius ir sąvokas |

Tavo pažangą matantys koordinatoriai nurodomi po kelio antrašte.

## Pranešimai ir automatizavimas

Ši skiltis pranešimų nesiunčia. Automatiškai pasižymintys žingsniai tikrinami kiekvieną kartą
atvėrus kelią.

## Techninė informacija

- Kelias: `ResolveLearningPath` (sluoksniai iš `authorization_duties`; atstovo pareigos – su
  `StudentRepresentation` ar `StudentRepCoordination` atsakomybe; tipai su protėviais per
  `getParentsAndSelf()`). Pateikimas – `LearningPathPresenter`.
- Etapas: pagal anksčiausios atstovo pareigybės pradžią; pabaiga – iš pareigų datų arba kadencijos
  (`ResolveCadenceForDuty`).
- Kadencijai kartojami žingsniai (`per_term`) įskaitomi, jei atlikti ne anksčiau kaip 90 dienų iki
  kadencijos pradžios. `updated` – kai `documents.sharepoint_modified_at` vėlesnis už atlikimą.
- Sisteminiai žingsniai (`is_system`, raktai `SystemStepRegistry`) įrašomi migracija; jų atlikimas
  skaičiuojamas skaitant ir nesaugomas. Kuratoriai keičia tik etapą, tvarką ir rodymą; ištrinti negalima.
- Teisės: `learningSteps.*.padalinys` – tik padalinio sluoksnis, `learningSteps.*.*` – visi; sąvokos –
  `learningSteps.update.*`. Perdavimo užrašai – `HandoverNotePolicy`; „Mano atstovai“ –
  `GetCoordinatedRepresentatives` per `ResponsibilityResolver`.
- Bandomasis turinys: `LearningPathPilotSeeder` (paleidžiamas ranka, kartojant nieko nedubliuoja).
