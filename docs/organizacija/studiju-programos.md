---
doc_status: reviewed
title: Studijų programos
area: studyPrograms
models: [StudyProgram]
last_reviewed: 2026-10-01
tests:
  - tests/Feature/Admin/Management/StudyProgramControllerTest.php
  - resources/js/Components/AdminForms/__tests__/StudyProgramForm.component.test.ts
---

# Studijų programos

**Studijų programos** – tai Vilniaus universiteto bakalauro, magistro, vientisųjų, doktorantūros
ir profesinių studijų programų katalogas, suskirstytas pagal fakultetinius padalinius.

Studijų programos platformoje priskiriamos [narių pareigybėms](/organizacija/pareigybes#priskirti-nari)
(dabar naudojama tik kuratoriams).

Studijų programų skiltis pasiekiama adresu `/mano/studyPrograms`.

## Kaip tai veikia

### Pakopos ir laipsniai {#laipsniai}

Kiekviena studijų programa turi nustatytą **studijų pakopą** (`degree`):

| Pakopa / Laipsnis | Reikšmė | Kodas sistemoje |
|---|---|---|
| **Bakalauras (BA)** | Pirmosios pakopos universitetinės studijos | `BA` |
| **Magistras (MA)** | Antrosios pakopos universitetinės studijos | `MA` |
| **Doktorantūra (PhD)** | Trečiosios pakopos (doktorantūros) studijos | `PHD` |
| **Vientisosios studijos (Integrated Studies)** | Vientisosios studijos (pvz., medicina, teisė) | `INTEGRATED_STUDIES` |
| **Profesinės pedagogikos studijos (Professional Pedagogy)** | Profesinės podiplominės pedagoginės studijos | `PROFESSIONAL_PEDAGOGY` |
| **Kita (Other)** | Kitos studijų formos ar kursai | `OTHER` |

### Priklausomybė padaliniui ir pavadinimas {#padalinys-ir-pavadinimas}

- Kiekviena studijų programa priklauso konkrečiam **padaliniui** (`tenant_id`).
- Programos pavadinimas įrašomas dviem kalbomis: **lietuvių** ir **anglų**.
- Pavadinimas turi būti unikalus tame padalinyje: bandant tame pačiame padalinyje sukurti programą
  jau naudojamu pavadinimu, sistema parodo duomenų patikros klaidą.

### Apsauga nuo trynimo {#apsauga}

Studijų programa yra glaudžiai susijusi su studentų atstovavimo istorija:

- Jei programa yra priskirta bent vienam nariui (esamame ar buvusiame pareigybės laikotarpyje),
  jos **ištrinti negalima**.
- Bandant ištrinti tokią programą, sistema veiksmą sustabdo ir parodo klaidos pranešimą su tiksliu
  susietų narių skaičiumi (pvz., *Studijų programa priskirta 3 narių laikotarpiams, todėl jos negalima ištrinti*).
- Norint pašalinti pasenusią programą su istorija, naudojamas **programų sujungimas**.

## Veiksmai

### Programų sąrašas ir filtrai {#sarasas}

Programų sąraše (`/mano/studyPrograms`) pateikiamos visos pasiekiamos studijų programos:

- **Paieška**: galima ieškoti pagal programos pavadinimą, laipsnį ar padalinio trumpinį.
- **Filtrai**:
  - *Laipsnis* – atrenka programas pagal pakopą (Bakalauras, Magistras ir kt.).
  - *Padalinys* – atrenka konkretaus padalinio programas (aktualu Centrinio biuro koordinatoriams).
- **Rikiavimas**: pagal pavadinimą nuo A iki Z arba nuo Z iki A.
- **Šiukšlinė**: viršuje rodo ištrintų programų skaičių ir leidžia peržiūrėti bei atkurti pašalintus įrašus.

### Naujos programos kūrimas {#kurimas}

Nauja studijų programa kuriama paspaudus **Nauja studijų programa** sąrašo viršuje (`/mano/studyPrograms/create`):

1. **Pavadinimas** – įrašyk programos pavadinimą lietuvių ir anglų kalbomis (pvz., *Programų sistemos* / *Software Engineering*).
2. **Laipsnis** – dešinėje esančiame bloke *Priskyrimas* pasirink studijų pakopą.
3. **Padalinys** – pasirink padalinį, kuriam ši programa priklauso.
4. Spausk **Išsaugoti**.

### Programos redagavimas {#redagavimas}

Norėdamas pakeisti esamos programos duomenis, sąraše paspausk jos pavadinimo arba per veiksmų meniu pasirink **Redaguoti** (`/mano/studyPrograms/{id}/edit`). Galima atnaujinti pavadinimus, pakopą arba pakeisti priskirtą padalinį.

### Programų sujungimas {#sujungimas}

Jei sistemoje atsirado dublikatų (pavyzdžiui, ta pati programa įvesta su nežymia rašybos klaida) arba
senoji programa buvo pervadinta:

1. Studijų programų sąraše viršuje spausk **Sujungti programas** (įsijungia pasirinkimo režimas).
2. Pažymėk bent dvi programas, kurias nori sujungti.
3. Apačioje paspausk mygtuką **Sujungti** – atsidarys sujungimo langas.
4. Pasirink, kurią programą **pasilikti kaip pagrindinę**.
5. Patvirtink sujungimą:
   - Visi narių laikotarpiai (`dutiables`), kurie buvo priskirti naikinamoms programoms, automatiškai perkeliami į pagrindinę programą.
   - Prijungiamos programos perkeliamos į šiukšlinę.
   - Narių veiklos istorija neprarandama.

### Šiukšlinė ir galutinis trynimas {#trynimas}

- Nenaudojamą programą, kuri neturi priskirtų narių, galima ištrinti formos apačioje paspaudus **Ištrinti** (perkeliama į šiukšlinę).
- Ištrintą programą galima bet kada **atkurti** šiukšlinės rodinyje.
- **Galutinis trynimas visam laikui yra blokuojamas** (`trash.blockers.membership_history`), jei programa turi narystės istoriją (`dutiables`).

## Kas ką gali {#teises}

| Veiksmas | Studentų atstovas | Komunikacijos koordinatorius | Centrinio biuro koordinatoriai | Superadministratorius |
|---|---|---|---|---|
| Matyti studijų programas | – | ✓, savo padalinio | ✓, visų padalinių | ✓ |
| Sukurti naują programą | – | ✓, savo padalinyje | ✓, visų padalinių | ✓ |
| Redaguoti programą | – | ✓, savo padalinyje | ✓, visų padalinių | ✓ |
| Sujungti programas | – | ✓, savo padalinyje | ✓, visų padalinių | ✓ |
| Ištrinti programą (jei nenaudojama) | – | ✓, savo padalinyje | ✓, visų padalinių | ✓ |
| Ištrinti programą visam laikui | – | – | – | ✓ (tik be istorijos) |

Centrinio biuro koordinatoriai lentelėje – **Centrinio biuro komunikacijos koordinatorius**.
Studentų atstovų koordinatorius neturi tiesioginių teisių kurti ar trinti studijų programų katalogo įrašų, tačiau gali priskirti studentus prie jau esančių programų per pareigybių valdymo langą.

## Pranešimai ir automatizavimas {#pranesimai}

Studijų programų kūrimas, atnaujinimas ar trynimas **nesiunčia** automatinių pranešimų ar el. laiškų.

Pakeitus programos duomenis ar sujungus programas, pasikeitimai iš karto atsispindi:

- Narių profiliuose ir pareigybių valdymo languose.
- Viešosios svetainės kontaktų puslapiuose, kur kontaktai grupuojami pagal studijų programas.

## Techninė informacija {#technine-informacija}

Šis skyrius skirtas tiems, kurie tvarko roles ar tikrina sistemos elgseną.

### Teisės

- Studijų programų prieigą reglamentuoja `StudyProgramPolicy`.
- `studyPrograms.read.padalinys`, `studyPrograms.create.padalinys`, `studyPrograms.update.padalinys`,
  `studyPrograms.delete.padalinys` suteikia padalinio apimties teises.
- Visuotiniai leidimai `studyPrograms.*` suteikia prieigą prie visų padalinių programų.
- Modelis `StudyProgram` susietas su vienu padaliniu (`$hasManyTenants = false`).

### Kaip tai įgyvendinta

- Modelis `StudyProgram` naudoja `HasFactory`, `HasTranslations`, `HasUlids`, `SoftDeletes` ir `GuardsForceDeleteWhenReferenced`.
- Laukas `name` yra išverčiamas (`translatable = ['name']`).
- Pakopos apibrėžtos išvardijamajame tipe `DegreeEnum`: formos parinktys gaunamos per `DegreeEnum::getFormOptions()`.
- Apsaugą nuo trynimo užtikrina `StudyProgramController::destroy`: patikrina `Dutiable::where('study_program_id', $studyProgram->id)->count()`.
- Programų sujungimą valdo `StudyProgramController::mergeStudyPrograms` pagal `MergeStudyProgramsRequest`: duomenų bazės transakcijoje perkelia `Dutiable` įrašus į tikslinę programą ir ištrina šaltinius.
- Galutinio trynimo blokatorius `forceDeleteBlockedReason` tikrina ryšį `dutiables`.
