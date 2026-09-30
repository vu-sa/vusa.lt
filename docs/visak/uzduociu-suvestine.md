---
title: Užduočių suvestinė
area: tasks
models: [Task]
last_reviewed: 2026-09-27
tests:
  - tests/Feature/Tasks/TaskSummaryTest.php
  - tests/Feature/Admin/Dashboard/UserTasksTest.php
  - resources/js/Pages/Admin/Tasks/__tests__/IndexTask.component.test.ts
  - resources/js/Pages/Admin/Dashboard/__tests__/ShowAtstovavimasPadaliniai.component.test.ts
  - tests/Browser/VisakOverviewPagesTest.php
---

# Užduočių suvestinė

**ViSAK → Užduotys** (`/mano/tasks/summary`) yra tas pats užduočių sąrašas kaip
[Mano → Užduotys](/mano/uzduotys), tik rodomos ne tavo, o **visų padalinio atstovų** užduotys. Čia
koordinatorius mato, kas vėluoja ir kam kokia užduotis priskirta.

Kaip veikia užduotys, jų tipai, filtrai, veiksmai ir priminimai, aprašyta puslapyje
[Užduotys](/mano/uzduotys). Čia aprašoma tik tai, kuo suvestinė skiriasi.

<DocScreenshot name="tasks-summary" alt="Užduočių suvestinė: greitieji filtrai, padalinio užduotys ir pasirinktos užduoties informacija" caption="Suvestinėje matyti visų padalinio atstovų užduotys ir kam jos priskirtos." href="/mano/tasks/summary" />

## Kaip tai veikia

Užduotis pati neturi padalinio. Ji priklauso **atsakingųjų padaliniams**: jei užduotis priskirta
MIF institucijos atstovui, ji rodoma MIF suvestinėje. Todėl suvestinėje nerodomos:

- užduotys, kurioms niekas nepriskirtas;
- užduotys, susijusios su žmogumi, o ne su posėdžiu, rezervacija ar institucija.

Be to, užduotis rodoma tik tada, jei gali atidaryti ir jos **objektą**: posėdžio užduotį matai tik
turėdamas teisę matyti to padalinio posėdžius. Taip užduotis neatskleidžia to, ko pats matyti
negalėtum. Užduotys, kurių objektas ištrintas, lieka matomos, kad jas būtų galima sutvarkyti.

## Veiksmai

Be bendrų [užduočių filtrų](/mano/uzduotys#filtrai) suvestinėje yra:

- **Priskirtos man** – greitasis filtras su skaičiumi: tik tos padalinio užduotys, kurios priskirtos ir tau.
- **Padalinys** – rodomas, jei gali matyti daugiau nei vieno padalinio užduotis. Galima rinktis tik
  tuos padalinius, kurių užduotis matai.

Suvestinę galima atidaryti ir iš [Padalinių apžvalgos](/visak/padaliniai) skaičiaus **Atviros
užduotys**.

## Kas ką gali {#teises}

| Rolė | Ką mato suvestinėje |
|---|---|
| Centrinio biuro studentų atstovų koordinatorius | Visų padalinių posėdžių ir institucijų užduotis. Rezervacijų užduočių nemato – jas tvarko [išteklių administratoriai](/rezervacijos/#istekliu-administratorius) |
| Studentų atstovų koordinatorius, Studentų atstovas | Suvestinė neprieinama. Savo užduotis mato [Mano → Užduotys](/mano/uzduotys) |
| Kiti nariai | Suvestinė neprieinama, skiltis nerodoma |

Matyti užduotį suvestinėje dar nereiškia, kad gali ją pažymėti atlikta: varnelę matai tik prie
užduočių, kurias gali keisti (esi atsakingas arba tavo rolė leidžia keisti padalinio užduotis).
Plačiau – [Užduotys: Kas ką gali](/mano/uzduotys#teises).

## Techninė informacija {#technine-informacija}

- Suvestinę atidaro `tasks.read.padalinys` (`TaskPolicy::viewAny`). `tasks.read.*` apima visus padalinius.
- Užduočių aprėptis – `BuildTaskIndexQuery::tenantScope`: užduoties padaliniai gaunami per
  atsakinguosius (`Task::tenants()`). Objekto teisės tikrinamos pagal `meetings.read.padalinys`,
  `reservations.read.padalinys` ir `institutions.read.padalinys` tame padalinyje.
- Pasirinkus padalinį, kurio užduočių matyti negali, sąrašas tampa tuščias (filtras neignoruojamas).
