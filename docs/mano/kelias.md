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
---

# Mano kelias

<ChangelogNote date="2026-11-09" title="Ruošiama mokymosi erdvė">

Pradžioje pridėta skiltis „Mano kelias“. Kol ruošiami mokymosi ir redagavimo puslapiai, skirtukuose rodoma informacija apie ruošiamą turinį.

</ChangelogNote>

## Susitarimai

Mokymosi žingsniai, sąvokos ir patirties perdavimas telkiami vienoje vietoje.
Skiltis skirta pasiruošti pareigoms ir išsaugoti patirtį kitam atstovui.

## Kaip tai veikia

Atvėrus [Mano kelią](app:/mano/learning), matomi skirtukai „Mano kelias“, „Žinynas“,
„Sąvokos“ ir „Perdavimas“. Atsakingam koordinatoriui taip pat matomas skirtukas „Mano atstovai“.

Šiame etape puslapiai dar nerodo mokymosi žingsnių ir neleidžia redaguoti turinio.
Mokymosi ir perdavimo sąsajos bus pildomos toliau.

## Veiksmai

Pasirink Pradžia → Mano kelias ir atverk norimą skirtuką.

## Kas ką gali {#teises}

| Kas | Kas prieinama dabar |
|---|---|
| Kiekvienas prisijungęs narys | Mano kelio, žinyno, sąvokų ir perdavimo skirtukai |
| Atsakingas studentų atstovų koordinatorius | Papildomai „Mano atstovai“ |
| Mokymosi turinį kurti galintis narys | „Sukurti mokymosi žingsnį“ per „+ Sukurti“; forma dar ruošiama |

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
