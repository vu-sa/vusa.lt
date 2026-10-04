---
doc_status: reviewed
title: Dokumentai
area: documents
models: [Document]
last_reviewed: 2026-10-02
tests:
  - tests/Feature/Admin/Resources/DocumentControllerTest.php
  - tests/Feature/DocumentSyncTest.php
  - tests/Feature/DocumentPermissionRevocationTest.php
  - tests/Feature/DocumentSyncMissingModelTest.php
  - tests/Feature/Meetings/MeetingDocumentTest.php
---

# Dokumentai

Dokumentai – viešai skelbiami VU SA ir padalinių nuostatai, ataskaitos, veiklos planai ir
protokolai. Archyvą atverk per **ViSAK → Dokumentai** (`/mano/documents`). Dokumentus
tvarkantiems jis taip pat pasiekiamas Svetainės srityje.

## Susitarimai {#susitarimai}

### Ką keliame į dokumentų naršyklę

- VU SA P nuostatus;
- VU SA P tarpines ir metines ataskaitas;
- VU SA P metų veiklos planus;
- ataskaitinių-rinkiminių, neeilinių rinkiminių ir neeilinių ataskaitinių-rinkiminių konferencijų
  protokolus;
- kolegialaus valdymo organo (Valdybos, VU SA MIF atveju – Tarybos) protokolus.

### Kaip keliame

- Dokumentus kelia VU SA P administratoriai. Studentų iniciatyvos dokumentų kol kas nekelia.
- Protokolai keliami į archyvą ir svetainę **.pdf** formatu. Šablonai gali būti .docx ar kito
  formato.
- Jei padalinyje veikia tarptautiniai studentai, dokumentai keliami lietuvių ir anglų kalbomis.
- Pastebėjus klaidą įkeltame dokumente, jis ištrinamas, pataisomas ir įkeliamas iš naujo.

## Kaip tai veikia

### Archyvas ir susieti failai {#archyvas}

Archyvo įrašas saugo dokumento duomenis ir viešą SharePoint nuorodą. Pats failas lieka
SharePoint. Dokumento rūšis, data, kalba ir institucija padeda jį rasti ir viešoje svetainėje.

Prie posėdžio ar pareigybės skirtuke **Failai** įkelti failai nėra archyvo įrašai ir viešai
nerodomi – žr. [Įrašų failai](/visak/failai). Bendrą integraciją paaiškina
[SharePoint integracija](/sistema/sharepoint).
Posėdžio dokumentų susiejimas aprašytas [Posėdžių gide](/visak/posedziai).

<ChangelogNote version="v3.0" date="2026-10-02" title="Dokumentų archyvas pasiekiamas visiems nariams">

Dokumentus naršyk be valdytojo rolės. Importavimo, sinchronizavimo ir šalinimo veiksmai lieka
juos tvarkantiems; pirmą kartą sąrašas atrenkamas pagal tavo padalinius ir centrinę VU SA.

</ChangelogNote>

### Sąrašas ir peržiūra {#sarasas}

Ieškok pagal pavadinimą, atverk filtrus arba pasirink greitąjį dokumentų rūšies filtrą.
Pradinius padalinių filtrus gali pakeisti. Lentelėje matai datą, pavadinimą, rūšį, instituciją
ir kalbą; peržiūros rodinyje – daugiau dokumento informacijos.

Paspaudęs viešą nuorodą turintį pavadinimą, atversi failą naujame naršyklės lange.
Jei nuorodos nėra, pavadinimas nėra aktyvus. Dokumentų valdytojai papildomai mato
sinchronizavimo būseną: **Laukiama**, **Importuota**, **Sinchronizuojama**,
**Sinchronizuota** arba **Nepavyko**.

## Veiksmai {#veiksmai}

### Įkelti iš SharePoint

1. Paruošk dokumentą SharePoint archyve pagal [susitarimus](#susitarimai).
2. Dokumentų sąraše spausk **Įkelti iš SharePoint** ir pasirink dokumentus.
3. Patvirtink pasirinkimą. Platforma importuoja jų duomenis ir parengia viešą prieigą.
4. Patikrink pavadinimą, instituciją, kalbą ir viešą nuorodą.

Jei importavimo mygtuko nematai, patikrink savo rolę. SharePoint pasirinkimo langui taip pat
reikia saugaus HTTPS ryšio. Dokumento turinį ir archyvo metaduomenis keisk SharePoint;
sąraše atskiros dokumento redagavimo formos nėra.

### Atnaujinti duomenis {#sinchronizavimas}

Pakeitęs dokumentą SharePoint, jo veiksmuose pasirink **Atnaujinti iš SharePoint**.
**Sinchronizuoti visus** paleidžia dokumentų, kurių sinchronizacija nepavyko, dar nebaigta
arba duomenys senesni nei 24 valandos, atnaujinimą.

Pranešimas apie užduoties įtraukimą į eilę dar nereiškia, kad sinchronizacija baigta.
Vėliau atnaujink sąrašą ir patikrink būseną bei nuorodą. Jei lieka **Nepavyko**, patikrink,
ar failas vis dar yra SharePoint ir ar jo duomenys tinkami, tada bandyk atnaujinti dar kartą.
Jei nepavyksta, pateik [pagalbos užklausą](/sistema/pagalbos-uzklausos) su dokumento pavadinimu
ir atliktais žingsniais.

### Pašalinti iš archyvo {#trynimas}

Dokumento veiksmuose pasirink **Ištrinti** ir patvirtink. SharePoint failas nepašalinamas,
bet panaikinamas platformos dokumento įrašas, o jo viešos prieigos leidimo atšaukimas
vykdomas fone. Jei nuoroda naudojama kitur, prieš šalindamas patikrink, ką paveiks jos panaikinimas.
Dokumentų sąraše šiukšlinės ar atkūrimo veiksmo nėra.

## Kas ką gali {#teises}

| Veiksmas | Bet kuris prisijungęs narys | Padalinio dokumentų valdytojas | Super Admin |
|---|---|---|---|
| Naršyti archyvą ir atverti viešas nuorodas | ✓ | ✓ | ✓ |
| Importuoti iš SharePoint ir paleisti bendrą sinchronizavimą | – | ✓ | ✓ |
| Atnaujinti ar šalinti dokumentą | – | ✓ Savo padalinio | ✓ Visų padalinių |

**Padalinio dokumentų valdytojas** – atskira rolė. Vien išteklių administratoriaus rolė
nesuteikia dokumentų valdymo. Bendras sinchronizavimas atrenka atnaujintinus archyvo įrašus;
jis nėra dabartinio sąrašo filtrų veiksmas.

## Pranešimai ir automatizavimas {#pranesimai}

Sinchronizavimo užduotys vykdomos fone. Jos atnaujina duomenis ir būseną;
į eilę įtrauktas dokumentas, ištrintas iki užduoties vykdymo, nebeatkuriamas.
Dokumentą pašalinus, atskira užduotis atšaukia SharePoint viešos prieigos leidimą.

## Techninė informacija {#technine-informacija}

- Sąrašą ir veiksmus valdo `DocumentController`; `index` leidžia naršyti be valdymo rolės.
- `documents.create.padalinys` leidžia importuoti ir paleisti `documents.bulk-sync`;
  pavienio atnaujinimo bei šalinimo apimtį tikrina `DocumentPolicy`.
- Sąsaja: `IndexDocument.vue`, `useTypesenseCollectionSource`, SharePoint failų pasirinkimas.
- Atnaujinimas: `SyncDocumentFromSharePointJob`; leidimo atšaukimas: `RevokeSharepointPermissionJob`.
- `DocumentControllerTest` tikrina naršymą be valdymo veiksmų, importą, sinchronizavimą ir
  padalinių ribas. `DocumentSyncTest` ir `DocumentPermissionRevocationTest` tikrina foninę eigą;
  `MeetingDocumentTest` – dokumentų susiejimą su posėdžiais.
