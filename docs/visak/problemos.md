---
title: Problemos
area: problems
models: [Problem]
last_reviewed: 2026-09-27
tests:
  - tests/Feature/Admin/Problems/ProblemControllerTest.php
  - tests/Feature/Api/Admin/ProblemApiControllerTest.php
  - tests/Feature/Permissions/StudentRepresentativeRoleTest.php
  - tests/Feature/Permissions/BaselineAccessTest.php
---

# Problemos

Problemos – iššūkiai, kliūtys ar klausimai, su kuriais susiduria studentai ar atstovai: studijų
proceso, komunikacijos, išteklių ar kitos srities. Registruojant problemas kuriama bendra sprendimų
žinių bazė, žiniomis dalijamasi su kitais padaliniais ir ta pati problema nesprendžiama du kartus.

1. Užregistruok problemą, kai ji atsiranda (**+ Sukurti → Nauja problema**).
2. Pridėk detalių.
3. Kai problema išspręsta, užregistruok sprendimą.
4. Filtruok ir peržiūrėk problemas pagal padalinį, būseną ir kategoriją.

Skiltis pasiekiama adresu `/mano/problems`.

::: warning Rašoma
Šis puslapis dar rašomas. Kai bus baigtas, jame bus skyriai **Kaip tai veikia**, **Veiksmai**,
**Kas ką gali** ir **Pranešimai ir automatizavimas**, kaip [Rezervacijų](/rezervacijos/rezervacijos) skyriuje.
:::

## Kas ką gali {#teises}

Visų padalinių problemas mato **kiekvienas narys**, net be rolės – tai bendra žinių bazė.
Problemas kelia ir tvarko **studentų atstovai** ir **koordinatoriai**. Koordinatoriai rolę
**Problemų redaktorius** gauna automatiškai pagal pareigybės tipą „Koordinatorius (-ė)“ (žr.
[Rolės pagal pareigybės tipą](/pagrindai/teises#roles-pagal-pareigybes-tipa)) ir tvarko savo
padalinio problemas.

| Veiksmas | Bet kuris narys | Studentų atstovas | Problemų redaktorius |
|---|---|---|---|
| Matyti visų padalinių problemas | ✓ | ✓ | ✓ |
| Kelti problemą | – | ✓, savo padalinyje | ✓, savo padalinyje |
| Redaguoti problemą | – | ✓, **bet kurią** savo padalinio | ✓, bet kurią savo padalinio |

::: info Studentų atstovas redaguoja visas padalinio problemas
Kol kas studentų atstovas gali keisti ne tik savo iškeltas, bet ir kolegų to paties padalinio
problemas. Kitų padalinių problemų keisti negali.
:::

## Techninė informacija {#technine-informacija}

### Teisės

- Skaityti: `ProblemPolicy::viewAny` ir `view` leidžiami visiems; sąrašas nefiltruojamas pagal teises.
- Problemų redaktorius: `problems.create.padalinys`, `problems.update.padalinys`.
- Studentų atstovas: `problems.create.padalinys`, `problems.update.padalinys`.
