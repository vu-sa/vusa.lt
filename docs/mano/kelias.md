---
title: Mano kelias
doc_status: reviewed
last_reviewed: 2026-11-09
tests:
  - tests/Feature/Admin/Learning/ResolveLearningPathTest.php
  - tests/Feature/Admin/Learning/LearningRoutesTest.php
  - tests/Feature/Admin/Learning/LearningPoliciesTest.php
  - tests/Feature/Admin/Learning/LearningWritesTest.php
  - tests/Feature/Admin/Learning/LearningStepProgressControllerTest.php
  - tests/Feature/Admin/Learning/SystemStepDetectorsTest.php
  - resources/js/Features/Admin/Learning/__tests__/LearningTabs.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningPathView.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningStepRow.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/BrowserNotificationSetup.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/InstallationInstructions.component.test.ts
---

# Mano kelias

<ChangelogNote date="2026-11-09" title="Mano kelias ir pažanga">

Mano kelyje žingsniai išdėstyti pagal kadencijos etapus. Galima pažymėti pažangą,
atverti šaltinį, peržiūrėti sąvokas ir dalytis įžvalgomis prie žingsnio.

</ChangelogNote>

## Susitarimai

Mokymosi žingsniai, sąvokos ir patirties perdavimas telkiami vienoje vietoje.
Skiltis skirta pasiruošti pareigoms ir išsaugoti patirtį kitam atstovui.

## Kaip tai veikia

Atvėrus [Mano kelią](app:/mano/learning), matomi skirtukai „Mano kelias“, „Žinynas“,
„Sąvokos“ ir „Perdavimas“. Atsakingam koordinatoriui taip pat matomas skirtukas „Mano atstovai“.

Dabartinis etapas pažymėtas „dabar“ ir išskleistas. Kiti etapai suskleisti, bet juos galima
atverti bet kada. Prie kiekvieno etapo rodomas atliktų ir visų žingsnių skaičius.
Žingsniai sugrupuoti pagal pagrindą, institucijos ar pareigų tipą ir padalinį.

Atvėrus žingsnį matoma, kam jo reikia, ir nuoroda į šaltinį. Jei dokumentas dar neviešas,
nuoroda nerodoma. „Įžvalgos“ atveria žingsnio puslapio diskusiją. Žingsnio puslapyje taip pat
matomi susietų sąvokų paaiškinimai: paspaudus sąvoką atsiveria apibrėžimas.

Po keliu pateikiamas pasiruošimas kiekvienam posėdžiui pagal institucijos tipą. Tai kartojamas
priminimas, todėl atlikimo žymų jame nėra.

## Veiksmai

1. Atverk [Mano kelią](app:/mano/learning) ir išskleisk žingsnį.
2. Perskaityk paaiškinimą, atverk šaltinį arba atlik nurodytą veiksmą.
3. Spausk **Perskaičiau** arba **Atlikau**. Rekomenduojamą ar papildomą žingsnį galima **Praleisti**.
4. Savo žymą galima panaikinti paspaudus **Grąžinti**. Automatiškai atliktas žingsnis taip ir pažymėtas;
   jo žyma priklauso nuo veiklos platformoje.

Diegimo žingsnyje pateiktos telefono ir kompiuterio instrukcijos. Naršyklės pranešimų leidimo
prašoma tik paspaudus įjungimo mygtuką. Kontaktų žingsniuose rodomi koordinatoriai ir savo
atstovaujamų institucijų pirmininkai bei dabartinės kadencijos sekretoriai.

## Kas ką gali {#teises}

| Kas | Kas prieinama dabar |
|---|---|
| Kiekvienas prisijungęs narys | Savo kelias ir pažangos žymos, žingsnio puslapis, sąvokų paaiškinimai ir įžvalgos |
| Atsakingas studentų atstovų koordinatorius | Papildomai „Mano atstovai“ ir atstovo kelio peržiūra be pažangos keitimo |
| Mokymosi turinį kurti galintis narys | „Sukurti mokymosi žingsnį“ per „+ Sukurti“; forma dar ruošiama |

## Pažangos būsenos

| Būsena | Ką reiškia |
|---|---|
| Laukia | Žingsnis dar neatliktas |
| Atlikta | Pažymėta paties nario arba automatiškai pagal veiklą |
| Praleista | Narys praleido rekomenduojamą ar papildomą žingsnį |
| Atnaujinta — peržiūrėk | Po perskaitymo pakeistas žinių šaltinis; jį galima peržiūrėti ir pažymėti iš naujo |

Antraštėje esminiai žingsniai skaičiuojami atskirai nuo bendros pažangos.
Tavo pažangą matantys koordinatoriai nurodomi po antrašte, jei jie priskirti.

## Pranešimai ir automatizavimas

Ši skiltis naujų pranešimų nesiunčia.

## Techninė informacija

Pagrindas apima sluoksniais sudaromą kelią, paveldimus institucijų ir pareigų tipus bei
dabartines ir būsimas pareigas. Etapai: prieš pradžią, pirmieji 60 dienų, visa kadencija,
paskutinės 45 dienos. Nežinoma pabaiga tikrinama per esamą kadencijų sprendiklį.

Pažangos būsenos: `pending`, `done`, `skipped`, `updated`. Papildomą ar rekomenduojamą
žingsnį praleidus, jis įskaitomas į bendrą atliktų žingsnių skaičių; esminio praleisti negalima.
Kadencijai kartojami žingsniai skaičiuojami nuo ankstyviausios nepasibaigusios atstovavimo
pareigybės pradžios. Automatinis atlikimas skaičiuojamas skaitant ir atskirai nesaugomas.

Žinių šaltinio `documents.sharepoint_modified_at` pakeitimas po atlikimo reiškia `updated`.
Perdavimo pastabas mato autorius, dabartiniai ir būsimi tos ar ją perėmusios pareigybės
nariai, atsakingas koordinatorius ir pareigybės padalinį administruoti galintys nariai.
Redaguoti pastabą gali tik autorius. Šios taisyklės paruoštos serverio pusėje.

Kuravimo teisės priklauso `learningSteps`: `padalinys` apima tik savo padalinio sluoksnio
žingsnius, `*` – visus. Sisteminiai žingsniai neištrinami; keičiami tik etapas, tvarka ir
aktyvumas. Sąvokų tvarkymas remiasi `learningSteps.update.*`.
