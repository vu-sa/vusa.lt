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
  - tests/Feature/Admin/Learning/LearningPathPresenterTest.php
  - tests/Feature/Admin/Learning/SystemStepDetectorsTest.php
  - tests/Feature/Seeders/LearningPathPilotSeederTest.php
  - resources/js/Features/Admin/Learning/__tests__/LearningLibrary.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/BrowserNotificationSetup.component.test.ts
  - tests/Feature/System/AdminNavigationCatalogTest.php
  - resources/js/Components/Layouts/Shell/__tests__/SectionTabs.component.test.ts
  - resources/js/Components/Layouts/Shell/__tests__/MobileContextBar.component.test.ts
  - resources/js/Components/Layouts/Shell/__tests__/MobileMenuPanel.component.test.ts
  - resources/js/Components/Layouts/Shell/__tests__/MobileBottomBar.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningPathView.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningCompletionControl.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningStepRow.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningStepForm.component.test.ts
---

# Mano kelias

**Mano kelias** padeda pasiruošti pareigoms: ką padaryti ir kada. Kelias sudaromas pagal tavo
pareigas, todėl Studijų programos komiteto atstovas mato kitus žingsnius nei Senato narys. Ką verta
perskaityti, rasi [Wiki](/wiki/straipsniai).

<ChangelogNote date="2026-11-11" title="Mano kelias">

Mano kelias pakeitė pareigybių ir institucijų tipų failus: vietoj failų sąrašo – žingsniai su
paaiškinimu, kam jų reikia, ir pažanga, kurią mato koordinatorius.

</ChangelogNote>

## Susitarimai

- **Skaitiniai – Wiki.** Sąvokos, dokumentai ir pristatymai aprašomi
  [Wiki straipsniuose](/wiki/straipsniai), o kelyje lieka tai, ką reikia padaryti. Žingsnio aprašyme
  galima nurodyti į straipsnį (`[[`).
- **Kiekvienas žingsnis paaiškina, kam jis reikalingas.** Aprašyme pirmiausia
  parašyk, kodėl verta – tai svarbiau už patį failą. Prireikus pridėk sąrašą, paveikslėlį ar YouTube vaizdo įrašą.
- **Esminių žingsnių nedaug.** Vienai auditorijai užtenka kelių esminių žingsnių; visa kita –
  rekomenduojama arba papildoma.

## Kaip tai veikia

### Auditorijos

Žingsniui gali būti priskirtos kelios auditorijos: visi nariai, pareigybės ar institucijos tipas
(įskaitant potipius), atsakomybė, konkreti pareigybė arba institucija. Kiekviena auditorija turi
savo svarbą, paaiškinimą ir, jei tai veiksmas, kadencijos etapą. Auditoriją galima apriboti padaliniu – tada ją turi atitikti tavo
pareigos būtent tame padalinyje.

Jei atitinki kelias auditorijas, rodoma didžiausia svarba ir tos auditorijos etapas. Vienodai
svarbioms auditorijoms pirmiau pasirenkamas ankstesnis etapas, tada mažesnis eilės numeris.
Matai visų tau tinkančių auditorijų paaiškinimus. Būsimos pareigos įskaitomos iš karto.

### Wiki ir pasiruošimas posėdžiui {#dokumentai-ir-skyriai}

Kadencijos etapų juostoje yra tik veiksmai. Kelio viršuje rodomas [Wiki](/wiki/straipsniai#pradzia)
blokas: kiek būtinų straipsnių jau perskaityta ir vienas siūlomas skaitinys. Pasiruošimas posėdžiui
rodomas kelio apačioje, atskirai kiekvienai tau tinkančiai auditorijai. Vienos auditorijos veiksmai
numeruojami iš eilės ir neturi pažangos žymų.

### Etapai ir svarba

Veiksmai išdėstyti pagal kadencijos etapus: **Prieš pradedant**, **Pirmieji mėnesiai** (pirmos 60
dienų), **Visą kadenciją** ir **Kadencijos pabaiga** (paskutinės 45 dienos). Dabartinis etapas
pažymėtas „dabar“. Visi etapai ir jų žingsniai rodomi iš karto. Žingsnio kairėje esantis langelis
žymi pažangą; varnelę galima uždėti ir nuimti tiesiog eilutėje. Eilutėje rodomas tik pavadinimas – paaiškinimai, diegimo ir pranešimų informacija pasiekiami paspaudus pavadinimą. Veiksmo mygtukas eilutės dešinėje rodo svarbą: esminio žingsnio – VU SA spalvos, rekomenduojamo – rėmelis didžiosiomis raidėmis, papildomo – paprastas tekstas. Žingsnio puslapyje pažangą galima žymėti prie pavadinimo, o veiksmų mygtukai pateikiami antraštės dešinėje. Žingsniai įžvalgų neturi – jos rašomos prie [Wiki straipsnių](/wiki/straipsniai). Koordinatoriaus nuotrauka, pareigos ir mygtukas „Parašyti“ rodomi tiesiai prie susipažinimo žingsnio.

Svarba nusako turinį, ne terminą: **Esminis** (būtina žinoti), **Rekomenduojamas**, **Papildomas**.
Esminio žingsnio praleisti negalima.

Žingsnio rūšys:

- **Veiksmas** – ką nors padaryti, pvz., susipažinti su koordinatoriumi ar perskaityti praeitų
  posėdžių protokolus. Dalis veiksmų pasižymi patys pagal veiklą platformoje (įjungti naršyklės pranešimai,
  užfiksuotas pirmas posėdis); tokie pažymėti žaibo piktograma. Kitus, pvz., pranešimų
  nustatymų pasirinkimą, pažymi pats.
- **Pasiruošimas posėdžiui** – kartojamas sąrašas kelio apačioje, pvz., perskaityti medžiagą, pasiklausti
  kursiokų. Jame pažangos žymų nėra.

Peržiūrėdamas kontaktinius duomenis taip pat patikrink savo nuotrauką – ją gali būti įkėlęs atstovų
koordinatorius. Atskiro nuotraukos įkėlimo žingsnio nėra, o nuotrauka neprivaloma.

Kadencijos pabaigoje
laukia žingsnis „Perduok patirtį kitam atstovui“: susitik su įpėdiniu arba jam parašyk ir pažymėk žingsnį pats.

### Skiltys {#skirtukai}

Mokymosi puslapiai pasiekiami antrajame darbo srities meniu. Pradžios srityje **Mano kelias** yra
antras skyrius; [Wiki](/wiki/) turi savo darbo sritį. Žingsnio puslapyje **Mano kelias** skirtuke
rodomas žingsnio pavadinimas. Žingsnių biblioteka pasiekiama per **Organizacija → Kelio žingsniai**.
**Pranešimai** atveriami varpeliu viršuje arba telefono apačios juostoje, ne skyriumi.
Telefone skyriai pasiekiami dabartinio skyriaus pasirinkime ir per **Meniu**.

- **Mano kelias** – tavo žingsniai ir pažanga.
- **Organizacija → Kelio žingsniai** – tik tiems, kas gali tvarkyti žingsnius (žr. [Kas ką gali](#teises)). Puslapis
  **Kelio žingsniai** rodo visus žingsnius su paieška. Greiti filtrai – **Veiksmai** ir **Pasiruošimas posėdžiams**; auditorija ir svarba – skiltyje **Filtrai**.

## Veiksmai

### Eiti savo keliu

1. Atverk [Mano kelią](app:/mano/learning).
2. Perskaityk paaiškinimą po pavadinimu ir atlik veiksmą.
3. Pažymėk langelį kairėje. Rekomenduojamą ar papildomą žingsnį gali **Praleisti** per **⋯** meniu.
4. Savo žymą panaikink nuimdamas varnelę. Automatiškai pažymėto langelio keisti negalima.
5. Skaitinius ir jų įžvalgas rasi [Wiki](app:/mano/knowledge).

### Pridėti žingsnį

1. Darbo srityje **Organizacija** pasirink **+ Sukurti** → **Naujas kelio žingsnis** (arba skyriuje **Kelio žingsniai** – **Naujas žingsnis**). Dokumentams
   ir pristatymams naudok [Wiki straipsnius](/wiki/straipsniai#prideti-straipsni).
2. Pasirink rūšį (**Veiksmas** arba **Pasiruošimas posėdžiui**), įrašyk pavadinimą ir aprašymą. Aprašyme galima formatuoti tekstą, pridėti paveikslėlių ir YouTube vaizdo įrašų.
3. Skiltyje **Kam skirta** spausk **Pridėti auditoriją**. Pasirink auditorijos tipą ir tikslą,
   svarbą, etapą ir, jei reikia, padalinį bei paaiškinimus lietuviškai ir angliškai. Jei gali tvarkyti
   tik savo padalinio žingsnius, padalinys privalomas. Etapą renkiesi tik veiksmams, o pasiruošimui
   posėdžiui rinkis instituciją arba jos tipą.
   Išsaugojęs auditoriją grįši į žingsnio formą – pats žingsnis išsaugomas tik jos mygtuku **Išsaugoti**.
4. Veiksmui gali pridėti mygtuką: jo tekstą ir nuorodą į platformos puslapį (`/mano/…`) arba kitą svetainę, abiem kalbomis.
5. Išsaugok.

## Kas ką gali {#teises}

| Kas | Ką gali |
|---|---|
| Kiekvienas narys | Matyti savo kelią, žymėti pažangą |
| Studentų atstovų koordinatorius (atsakingas už institucijas) | Matyti savo atstovų pažangą ir kelius puslapyje [ViSAK → Atstovai](/visak/atstovai) |
| Studentų atstovų koordinatorius | Atverti Organizacija → Kelio žingsniai, kurti ir redaguoti žingsnius, kurių visos auditorijos apribotos savo padaliniu |
| Centrinio biuro studentų atstovų koordinatorius | Kurti ir redaguoti visų auditorijų žingsnius |

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
- Pažymėtas atlikimas išlieka ir pasikeitus kadencijai.
- Sisteminiai žingsniai (`is_system`, raktai `SystemStepRegistry`) įrašomi migracija ir lieka tik ten,
  kur reikia valdiklio ar automatinio atlikimo; žingsniai, kuriuos atstovas pažymi pats, yra įprasti, prireikus su
  mygtuku (`action_label`, `action_url`). Sisteminių atlikimas skaičiuojamas skaitant ir nesaugomas. Juose keičiamas tik etapas, tvarka ir rodymas; ištrinti negalima.
- Teisės: `learningSteps.*.padalinys` – tik savo padaliniu apribotos auditorijos, `learningSteps.*.*` – visos (jos
  taip pat leidžia tvarkyti [Wiki straipsnių](/wiki/straipsniai#technine-informacija) auditorijas); atstovų pažanga –
  [Atstovai](/visak/atstovai#technine-informacija), per `ResponsibilityResolver`.
- Bandomasis turinys: `LearningPathPilotSeeder` (paleidžiamas ranka, kartojant nieko nedubliuoja).

Žingsnių svarbą rodo pažangos langelis: VU SA spalvos kraštas – esminis, vientisas – rekomenduojamas, punktyrinis – papildomas. Žaibo langelis ranka nekeičiamas; užvedus pelę arba sufokusavus rodomas paaiškinimas. Žaibas langelio viduje žymi automatiškai stebimą žingsnį: šviesus langelis – dar neatliktas, užpildytas – atliktas.
