---
title: Mano kelias
doc_status: reviewed
last_reviewed: 2026-11-11
tests:
  - tests/Feature/Admin/Learning/ResolveLearningPathTest.php
  - resources/js/Features/Admin/Learning/__tests__/ShowLearningStep.component.test.ts
  - tests/Feature/Admin/Learning/LearningRoutesTest.php
  - tests/Feature/Admin/Learning/LearningPoliciesTest.php
  - tests/Feature/Admin/Learning/LearningWritesTest.php
  - tests/Feature/Admin/Learning/LearningStepProgressControllerTest.php
  - tests/Feature/Admin/Learning/LearningLibraryTest.php
  - tests/Feature/Admin/Learning/LearningDocumentsTest.php
  - tests/Feature/Admin/Learning/LearningSectionsTest.php
  - tests/Feature/Admin/Learning/LearningPeopleTest.php
  - tests/Feature/Admin/Learning/LearningPathPresenterTest.php
  - tests/Feature/Admin/Learning/SystemStepDetectorsTest.php
  - tests/Feature/Seeders/LearningPathPilotSeederTest.php
  - resources/js/Features/Admin/Learning/__tests__/LearningLibrary.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/BrowserNotificationSetup.component.test.ts
  - tests/Feature/System/AdminNavigationCatalogTest.php
  - resources/js/Components/Layouts/Shell/__tests__/SectionTabs.component.test.ts
  - resources/js/Components/Layouts/Shell/__tests__/MobileContextBar.component.test.ts
  - resources/js/Components/Layouts/Shell/__tests__/MobileMenuPanel.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningDocuments.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningGlossarySteps.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningPathView.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningCompletionControl.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningStepRow.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningStepForm.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningSectionsSheet.component.test.ts
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
- **Esminių žingsnių nedaug.** Vienai auditorijai užtenka kelių esminių žingsnių; visa kita –
  rekomenduojama arba papildoma.
- **Įžvalgos – autoriaus nuomonė.** Prie žingsnio rašoma, kas dokumente svarbiausia ir kur žiūrėti;
  įžvalga pasirašyta ir su data, nes kontekstas keičiasi.

## Kaip tai veikia

### Auditorijos

Žingsniui gali būti priskirtos kelios auditorijos: visi nariai, pareigybės ar institucijos tipas
(įskaitant potipius), atsakomybė, konkreti pareigybė arba institucija. Kiekviena auditorija turi
savo svarbą, paaiškinimą ir, jei tai veiksmas, kadencijos etapą. Auditoriją galima apriboti padaliniu – tada ją turi atitikti tavo
pareigos būtent tame padalinyje.

Jei atitinki kelias auditorijas, rodoma didžiausia svarba ir tos auditorijos etapas. Vienodai
svarbioms auditorijoms pirmiau pasirenkamas ankstesnis etapas, tada mažesnis eilės numeris.
Matai visų tau tinkančių auditorijų paaiškinimus. Būsimos pareigos įskaitomos iš karto.

### Dokumentai ir skyriai

Kadencijos etapų juostoje yra tik užduotys. Dokumentai turi savo puslapį ir savo pažangą, o kelyje
rodomas jų blokas: kiek svarbiausių jau perskaityta ir vienas rekomenduojamas dokumentas.
Tie, kas gali tvarkyti žingsnius, dokumentus gali sudėti į skyrius, pvz., po mokymų – „Įvadiniai mokymai“. Ištrynus
skyrių dokumentai lieka. Pasiruošimas posėdžiui rodomas kelio apačioje, atskirai kiekvienai
tau tinkančiai auditorijai. Vienos auditorijos veiksmai numeruojami iš eilės ir neturi pažangos žymų.

Dokumentų sąraše **Pagal svarbą** pirmiausia rodo neperskaitytus dokumentus, suskirstytus į
**Būtina**, **Rekomenduojama** ir **Papildoma**. **Pagal skyrius** rodo skyrių aprašymus ir jų dokumentus;
be skyriaus likę dokumentai yra sąrašo gale, prie **Kita**. Pasirinktas rodinys išlieka nuorodoje.

<ChangelogNote date="2026-11-10" title="Auditorijos ir dokumentų skyriai">

Dokumentų blokas rodomas prieš etapų juostą. Po ja yra nuorodos į sąvokas ir patirties perdavimą,
o apačioje – pasiruošimas posėdžiui. Žingsnio puslapyje prie „Tau tai reiškia“ matomi paaiškinimai tavo auditorijoms.

</ChangelogNote>

### Etapai ir svarba

Veiksmai išdėstyti pagal kadencijos etapus: **Prieš pradedant**, **Pirmieji mėnesiai** (pirmos 60
dienų), **Visą kadenciją** ir **Kadencijos pabaiga** (paskutinės 45 dienos). Dabartinis etapas
pažymėtas „dabar“. Visi etapai ir jų žingsniai rodomi iš karto. Žingsnio kairėje esantis langelis
žymi pažangą; varnelę galima uždėti ir nuimti tiesiog eilutėje. Ilgesni paaiškinimai, diegimo ir pranešimų informacija pasiekiami paspaudus žingsnio pavadinimą arba „Atidaryti žingsnį“. Įžvalgų skaičius rodomas šalia pavadinimo. Žingsnio puslapyje pažangą galima žymėti prie pavadinimo, veiksmų mygtukai pateikiami antraštės dešinėje, o įžvalgos rodomos po turiniu. Sąvokos atveriamos paspaudus jų žymas; jas tvarkantys nariai redaktorių atveria per ⋯ → „Tvarkyti sąvokas“. Koordinatoriaus nuotrauka, pareigos ir mygtukas „Parašyti“ rodomi tiesiai prie susipažinimo žingsnio.

Svarba nusako turinį, ne terminą: **Esminis** (būtina žinoti), **Rekomenduojamas**, **Papildomas**.
Esminio žingsnio praleisti negalima.

Žingsnio rūšys:

- **Žinios** – perskaityti dokumentą ar nuorodą.
- **Veiksmas** – ką nors padaryti, pvz., susipažinti su koordinatoriumi ar perskaityti praeitų
  posėdžių protokolus. Dalis veiksmų pasižymi patys pagal veiklą platformoje (pasirinkti pranešimų nustatymai,
  sekama institucija, užfiksuotas pirmas posėdis); tokie pažymėti žaibo piktograma.
- **Pasiruošimas posėdžiui** – kartojamas sąrašas kelio apačioje, pvz., perskaityti medžiagą, pasiklausti
  kursiokų. Jame pažangos žymų nėra.

Peržiūrėdamas kontaktinius duomenis taip pat patikrink savo nuotrauką – ją gali būti įkėlęs atstovų
koordinatorius. Atskiro nuotraukos įkėlimo žingsnio nėra, o nuotrauka neprivaloma.

Sąvokų ir patirties perdavimo kortelės rodomos iš karto po dokumentų bloku, prieš kadencijos etapus.

### Šaltiniai ir kalba

Žingsnis vienas abiem kalboms, bet anglų kalbai galima nurodyti kitą dokumentą ar nuorodą; jei jos nėra,
rodomas lietuviškas šaltinis. Šaltiniu gali būti ir Archyve paslėptas dokumentas – tada atstovai mato
žingsnį ir paaiškinimą, o vietoj nuorodos – „neviešas“. Žingsnio pavadinimas atveria žingsnio puslapį. Šalia esantis „Skaityti dokumentą“ atveria šaltinį naujame skirtuke; veiksmo nuoroda rodoma, jei tokia yra.

Jei šaltinio dokumentas Archyve atnaujinamas jau perskaičius, žingsnis pažymimas
„Atnaujinta – peržiūrėk“.

### Skiltys {#skirtukai}

Mokymosi puslapiai pasiekiami antrajame darbo srities meniu.

Pradžios srityje **Mano kelias** yra antras skyrius. Rodyklė šalia jo pavadinimo atveria
žemiau išvardytus puslapius; skirtuko pavadinimas atitinka atvertą puslapį, o žingsnio puslapyje –
žingsnio pavadinimą. Telefone šie puslapiai pasiekiami dabartinio skyriaus pasirinkime ir per **Meniu**.

- **Mano kelias** – tavo žingsniai ir pažanga.
- **Dokumentai** – tavo kelio dokumentai vienoje vietoje, pagal svarbą tau arba pagal skyrius. Prie dokumento galima pažymėti pažangą ir
  atverti susietų sąvokų paaiškinimus.
- **Sąvokos** – žodynėlis (SPK, akreditacija, kvorumas…). Po apibrėžimu „Kur sutinkama“ rodo
  susietus žingsnius: nuoroda atveria dokumentą arba žingsnio puslapį su įžvalgomis.
- **Patirties perdavimas** – pirmtakų užrašai ir tavo užrašai įpėdiniui.
- **Atstovai** – tik studentų atstovų koordinatoriams ir superadministratoriams.
- **Biblioteka** – tik tiems, kas gali tvarkyti žingsnius (žr. [Kas ką gali](#teises)). Čia rodomi visi žingsniai su paieška ir
  filtrais pagal auditorijos tipą, skyrių ir rūšį. Redaguojamuose žingsniuose matomos žymos: dokumentas
  nebegalioja, dokumentas atnaujintas po peržiūros arba nėra šaltinio.

<ChangelogNote date="2026-11-10" title="Dokumentai ir aiškesnis kelias">

Žingsniai rodomi be išskleidimo, dokumentai turi savo skirtuką, o sąvokos – nuorodas į susietus žingsnius.
Biblioteka pasiekiama per rodyklę šalia skyriaus pavadinimo tik tiems, kas gali tvarkyti žingsnius.

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
2. Pasirink rūšį, įrašyk pavadinimą ir „Kodėl verta“.
3. Skiltyje **Kam skirta** spausk **Pridėti auditoriją**. Pasirink auditorijos tipą ir tikslą,
   svarbą, etapą ir, jei reikia, padalinį bei paaiškinimus lietuviškai ir angliškai. Jei gali tvarkyti
   tik savo padalinio žingsnius, padalinys privalomas. Etapą renkiesi tik veiksmams: dokumentai
   rikiuojami pagal svarbą ir skyrius, o pasiruošimui posėdžiui rinkis instituciją arba jos tipą.
   Išsaugojęs auditoriją grįši į žingsnio formą – pats žingsnis išsaugomas tik jos mygtuku **Išsaugoti**.
4. Dokumentui gali pasirinkti skyrių. Pasirink šaltinį: dokumentą iš Archyvo arba nuorodą. Anglišką šaltinį nurodyk tik tada,
   kai jis skiriasi.
5. Išsaugok. Žingsnio puslapyje gali susieti sąvokas.

### Tvarkyti dokumentų skyrius

1. Skirtuke **Tvarkyti** atverk **Skyriai**.
2. Spausk **Sukurti skyrių** arba **Redaguoti** prie esamo skyriaus. Įrašyk pavadinimus ir
   aprašymus abiem kalbomis, eilės numerį ir padalinį, jei jis privalomas.
3. Išsaugok. Skyrių galėsi pasirinkti redaguodamas dokumento žingsnį.
4. **Ištrinti** paprašys patvirtinimo. Skyriaus dokumentai liks sąraše **Kita**.

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
| Studentų atstovų koordinatorius | Atverti „Tvarkyti“, kurti ir redaguoti žingsnius, kurių visos auditorijos apribotos savo padaliniu, ir savo padalinio skyrius |
| Centrinio biuro studentų atstovų koordinatorius | Kurti ir redaguoti visų auditorijų žingsnius, skyrius ir sąvokas |

Tavo pažangą matantys koordinatoriai nurodomi po kelio antrašte.

## Pranešimai ir automatizavimas

Ši skiltis pranešimų nesiunčia. Automatiškai pasižymintys žingsniai tikrinami kiekvieną kartą
atvėrus kelią.

## Techninė informacija

- Kelias: `ResolveLearningPath` (auditorijos iš `authorization_duties`; atstovo pareigos – su
  `StudentRepresentation` ar `StudentRepCoordination` atsakomybe; tipai su protėviais per
  `getParentsAndSelf()`). Pateikimas – `LearningPathPresenter`.
- Etapas: pagal anksčiausios atstovo pareigybės pradžią; pabaiga – iš pareigų datų arba kadencijos
  (`ResolveCadenceForDuty`).
- Kadencijai kartojami žingsniai (`per_term`) įskaitomi, jei atlikti ne anksčiau kaip 90 dienų iki
  kadencijos pradžios. `updated` – kai `documents.sharepoint_modified_at` vėlesnis už atlikimą.
- Sisteminiai žingsniai (`is_system`, raktai `SystemStepRegistry`) įrašomi migracija; jų atlikimas
  skaičiuojamas skaitant ir nesaugomas. Juose keičiamas tik etapas, tvarka ir rodymas; ištrinti negalima.
- Teisės: `learningSteps.*.padalinys` – tik savo padaliniu apribotos auditorijos ir skyriai, `learningSteps.*.*` – visi; sąvokos –
  `learningSteps.update.*`. Perdavimo užrašai – `HandoverNotePolicy`; „Mano atstovai“ –
  `GetCoordinatedRepresentatives` per `ResponsibilityResolver`.
- Bandomasis turinys: `LearningPathPilotSeeder` (paleidžiamas ranka, kartojant nieko nedubliuoja).

Žingsnių svarbą rodo pažangos langelis: VU SA spalvos kraštas – esminis, vientisas – rekomenduojamas, punktyrinis – papildomas. Žaibo langelis ranka nekeičiamas; užvedus pelę arba sufokusavus rodomas paaiškinimas. Žaibas langelio viduje žymi automatiškai stebimą žingsnį: šviesus langelis – dar neatliktas, užpildytas – atliktas. Ilgesnius paaiškinimus išskleisk per „Daugiau“. Prie dokumentų susijusios sąvokos atveria apibrėžimus.
