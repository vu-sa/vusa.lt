---
doc_status: reviewed
title: Studijų rinkiniai
area: studySets
models: [StudySet]
last_reviewed: 2026-10-01
tests:
  - tests/Feature/Admin/StudySets/StudySetControllerTest.php
  - resources/js/Components/AdminForms/__tests__/StudySetForm.component.test.ts
---

# Studijų rinkiniai

Studijų rinkiniai skirti individualiųjų studijų (arba gretutinių / pasirenkamųjų dalykų) komplektams skelbti viešoje svetainėje adresu `/ind-komplektai`. Čia studentai gali rasti rekomenduojamus studijų dalykų paketus su aprašymais, kreditais ir studentų atsiliepimais.

Skiltis pasiekiama adresu `/mano/studySets`.

## Kaip tai veikia

- **Viešas puslapis:** paskelbti ir matomi rinkiniai rodomi viešoje svetainėje adresu `/ind-komplektai`, taip pat atitinkamo padalinio subdomene (pvz., `mif.vusa.lt/ind-komplektai`).
- **Rinkinio struktūra:** kiekvienas studijų rinkinys susideda iš:
  - **Bendrosios informacijos:** pavadinimo, aprašymo, rodymo eiliškumo ir matomumo jungiklio.
  - **Studijų dalykų sąrašo (`StudySetCourse`):** komplektui priklausančių dalykų pavadinimų, oficialių studijų kodų ir rikiavimo tvarkos.
  - **Dalykų atsiliepimų (`LecturerReview`):** prie konkrečių dalykų pridedamų studentų atsiliepimų apie dėstytojus, atsiskaitymų tvarką ar studijų eigą.

## Veiksmai

### Rinkinių sąrašas

Sąraše (`/mano/studySets`) matomi visi pasiekiami komplektai:

- Pateikiamas komplekto pavadinimas, padalinys, priskirtų dalykų skaičius, rikiavimo numeris ir matomumo būsena.
- Galima ieškoti pagal pavadinimą arba peržiūrėti ištrintus rinkinius paspaudus **Šiukšlinė**.

### Naujo rinkinio kūrimas

Puslapio viršuje spausk **Naujas studijų rinkinys** (arba eik adresu `/mano/studySets/create`):

1. **Padalinys** – pasirink, kuriam padaliniui priklauso šis rinkinys.
2. **Pavadinimas** – įrašyk komplekto pavadinimą lietuviškai ir angliškai (pvz., „Duomenų mokslo pagrindai“).
3. **Aprašymas** – pateik trumpą paaiškinimą apie komplekto tikslą ir kam jis rekomenduojamas.
4. **Rikiavimas** – įvesk eilės numerį (mažesnis skaičius rodomas anksčiau).
5. **Matomumas** – pažymėk, ar rinkinys iškart matomas viešoje svetainėje.
6. **Dalykų pridėjimas:**
   - Spausk **Pridėti dalyką**.
   - Įvesk dalyko pavadinimą ir oficialų kodą.
   - Jei turi atsiliepimų, prie dalyko pridėk atsiliepimą (atsiliepimo tekstas, dėstytojo vardas ir pavardė).
7. Spausk **Išsaugoti**. Kartu išsaugomi dalykai ir atsiliepimai.

### Redagavimas

Paspausk rinkinio pavadinimo sąraše arba veiksmų meniu pasirink **Redaguoti**. Redagavimo formoje gali:

- Keisti bendruosius rinkinio duomenis.
- Pridėti naujų dalykų ar pašalinti esamus.
- Koreguoti dalykų eiliškumą.
- Pridėti ar atnaujinti studentų atsiliepimus.

Atlikęs pakeitimus spausk **Išsaugoti**.

### Šalinimas ir atkūrimas

- **Perkėlimas į šiukšlinę:** veiksmų meniu pasirink **Ištrinti**. Rinkinys paslepiamas iš viešo puslapio ir perkeliamas į šiukšlinę.
- **Atkūrimas:** šiukšlinės sąraše pasirink **Atkurti**.
- **Galutinis ištrynimas:** šiukšlinėje pasirink **Ištrinti visam laikui**. Kartu pašalinami ir su šiuo rinkiniu susieti dalykai bei atsiliepimai.

## Kas ką gali {#teises}

| Veiksmas | Narys be rolės | Komunikacijos koordinatorius | Centrinio biuro komunikacijos koordinatorius |
|---|---|---|---|
| Matyti sąrašą | – | ✓ (savo padalinio) | ✓ (visus) |
| Sukurti rinkinį | – | ✓ (savo padaliniui) | ✓ (visiems) |
| Redaguoti rinkinį ir dalykus | – | ✓ (savo padalinio) | ✓ (visų) |
| Trinti į šiukšlinę / atkurti | – | ✓ (savo padalinio) | ✓ (visų) |
| Ištrinti visam laikui | – | ✓ (savo padalinio) | ✓ (visų) |

## Pranešimai ir automatizavimas {#pranesimai}

- Sukūrus, atnaujinus ar ištrynus studijų rinkinį jokie automatiniai pranešimai ar laiškai nesiunčiami.
- Pakeitimai viešame puslapyje `/ind-komplektai` atsiranda iš karto po formos išsaugojimo.

## Techninė informacija {#technine-informacija}

Šis skyrius skirtas administratoriams ir sistemos priežiūrai.

### Teisės

- Prieigą kontroliuoja `StudySetPolicy`.
- Leidimai: `studySets.create.padalinys`, `studySets.read.padalinys`, `studySets.update.padalinys`, `studySets.delete.padalinys` (ir visos platformos `studySets.*.*`).

### Kaip tai įgyvendinta

- Modeliai:
  - `App\Models\StudySet` (naudoja Spatie translatable `name` ir `description` laukams bei `SoftDeletes`).
  - `App\Models\StudySetCourse` (turi ryšį `belongsTo(StudySet::class)` ir `hasMany(LecturerReview::class)`).
  - `App\Models\LecturerReview`.
- Valdiklis: `App\Http\Controllers\Admin\StudySetController`.
- Visi ryšiai (`courses` ir `reviews`) sinchronizuojami duomenų bazės transakcijoje (`DB::transaction`).
- Viešasis valdiklis: `App\Http\Controllers\Public\StudySetController` atvaizduoja `resources/js/Pages/Public/ShowStudySets.vue`.
