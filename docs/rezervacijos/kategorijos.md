---
doc_status: reviewed
title: Kategorijos
area: resourceCategories
models: [ResourceCategory]
last_reviewed: 2026-09-30
tests:
  - tests/Feature/Admin/Reservations/ResourceCategoryControllerTest.php
  - tests/Feature/Api/Admin/ResourceCategoryApiControllerTest.php
  - tests/Browser/ReservationPagesTest.php
---

# Kategorijos

Kategorijos grupuoja išteklius pagal paskirtį, pavyzdžiui, „Garso technika“ ar „Atributika“. Jos
tvarkomos adresu `/mano/resourceCategories`. Kategorija priskiriama kuriant arba redaguojant
išteklių. Išteklių sąraše pagal ją galima filtruoti.

<DocScreenshot name="resource-categories" alt="Išteklių kategorijų sąrašas su pavadinimais, aprašymais ir veiksmais" caption="Kategorijos kuriamos ir redaguojamos tiesiai sąraše." />

## Kaip tai veikia

- Kategorijos yra **bendros visai organizacijai**: jos neturi padalinio, todėl MIF sukurta
  kategorija matoma ir FSF.
- Kategorijai galima parinkti **ikonėlę**. Ji rodoma šalia išteklių visų išteklių sąraše.

## Veiksmai

Kategorija neturi atskiro puslapio: ji kuriama ir redaguojama tiesiai sąraše, šoniniame lange.
Sąrašo eilutėje ir lentelėje galima atverti redagavimą arba ištrynimo patvirtinimą, jei turi
atitinkamą teisę.

## Kas ką gali {#teises}

Kategorijas mato ir tvarko
[išteklių administratoriai](/rezervacijos/#istekliu-administratorius). Kadangi kategorijos bendros,
bet kurio padalinio administratorius gali sukurti ar pervadinti kategoriją, kurią naudoja ir kiti.
Prieš trindamas kategoriją, pasitark su kitais padaliniais.

## Techninė informacija {#technine-informacija}

### Teisės

- Kategorijos neturi savų teisių: `ResourceCategoryPolicy` kiekvieną veiksmą tikrina pagal
  atitinkamą išteklių teisę (`resources.create|update|delete`, `padalinys` arba `*` apimtimi).
  Kategorijų sąrašą mato turintys `resources.update` – išteklius mato visi, todėl skaitymo teisė
  čia nieko nereikštų.
- `resourceCategories.*` teisės nesukurtos – jas tikrinant kategorijas galėtų tvarkyti tik super
  administratoriai.
