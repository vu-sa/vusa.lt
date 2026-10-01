---
doc_status: reviewed
title: Formos ir registracijos
area: forms
models: [Form, Registration]
last_reviewed: 2026-10-01
tests:
  - tests/Feature/Admin/Forms/FormControllerTest.php
  - tests/Feature/Admin/Forms/FormAccessTest.php
  - tests/Feature/Forms/StudentRepRegistrationTest.php
  - tests/Feature/Forms/RegistrationSecurityTest.php
  - tests/Feature/Forms/RegistrationWorkflowTest.php
  - tests/Feature/Forms/SendMemberRegistrationNotificationTest.php
  - tests/Feature/Forms/ConfirmMemberRegistrationTest.php
  - tests/Feature/Forms/MemberRegistrationNotificationMailTest.php
  - tests/Feature/Api/Admin/FormApiControllerTest.php
  - resources/js/Components/AdminForms/__tests__/FormForm.component.test.ts
  - resources/js/Features/Registrations/__tests__/RegistrationForm.component.test.ts
---

# Formos ir registracijos

**Formos ir registracijos** – tai anketų kūrimo ir atsakymų valdymo sistema. Skiltis skirta registracijoms
į renginius, vidinėms apklausoms, grįžtamojo ryšio rinkimui bei dviem specialioms organizacijos anketoms:
**narių registracijai** ir **studentų atstovų registracijai**.

Formų sąrašas pasiekiamas adresu `/mano/forms`, o kiekviena forma atveriama adresu `/mano/forms/{id}`.

## Kaip tai veikia

### Formos struktūra ir viešinimas {#struktura}

Kiekviena forma susideda iš:

- **Pavadinimo ir aprašymo** (lietuvių ir anglų kalbomis). Aprašymas pildomas raiškiojo teksto redaktoriumi (Tiptap) ir rodomas virš anketos laukų.
- **Viešo kelio** (`path`, pvz., `mokymai-2026`). Nustačius kelią, vieša anketa tampa pasiekiama adresu `vusa.lt/registracija/{path}` (lietuviškai) arba `vusa.lt/en/registration/{path}` (angliškai).
- **Priklausomybės padaliniui** (`tenant_id`). Kiekviena forma priklauso konkrečiam padaliniui, o jos matomumas ir redagavimas ribojamas padalinio teisėmis.
- **Paskelbimo laiko** (`publish_time`, neprivaloma). Nurodo datą ir laiką, nuo kada forma laikoma paskelbta.

### Formos laukai {#laukai}

Formoje galima laisvai sudaryti klausimų seką. Palaikomi laukų tipai:

| Laukas | Paskirtis | Galimi potipiai |
|---|---|---|
| **Tekstinis (`string`)** | Trumpas tekstinis atsakymas arba speciali įvestis | Tekstas (`text`), el. paštas (`email`), telefonas (`phone`), vardas ir pavardė (`name`) |
| **Ilgas tekstas (`text`)** | Išsamus atsakymas ar komentaras | Teksto sritis (`textarea`) |
| **Pasirinkimas (`enum`)** | Pasirinkimas iš fiksuotų parinkčių | Išskleidžiamasis sąrašas (`select`), vieno pasirinkimo mygtukai (`radio`) |
| **Loginis (`boolean`)** | Taip / Ne arba sutikimo varnelė | Varnelė (`checkbox`) |
| **Skaičius (`number`)** | Skaitinė reikšmė | Sveikasis skaičius |
| **Data (`date`)** | Datos pasirinkimas | Kalendoriaus data |

Laukams galima nustatyti **privalomumą** (`is_required`), paaiškinamąjį aprašymą ir numatytąją reikšmę.

#### Modelio parinktys {#modelio-parinktys}

Laukams su tipu `enum` galima įjungti **modelio parinktis** (`use_model_options`):

- **Padaliniai (`Tenant`)**: pasirinkimo sąrašas automatiškai užpildomas visais VU SA padaliniais.
- **Institucijos (`Institution`)**: sąrašas automatiškai užpildomas universiteto valdymo organais ir komisijomis.

### Atsakymų vientisumo apsauga {#vientisumas}

Kai forma jau turi bent vieną pateiktą atsakymą (`registrations_count > 0`):

- Sistemoje **užrakinamas laukų pridėjimas ir šalinimas**. Taip apsaugoma, kad nebūtų ištrinti klausimai, į kuriuos dalis žmonių jau atsakė, ir nebūtų pažeistas anksčiau pateiktų atsakymų vientisumas.
- Redaguoti galima formos pavadinimą, aprašymą, laukų paaiškinimus ir jų rodymo tvarką.

### Specialiosios registracijos {#specialiosios-registracijos}

Dvi formos sistemoje atlieka ypatingą vaidmenį ir yra susietos su automatiniais procesais:

1. **Narių registracija** (`member_registration_form_id`):
   - Bendra anketa norintiems tapti VU SA nariais.
   - Priklauso Centriniam biurui, tačiau pretendentas anketoje pasirenka padalinį arba iniciatyvą.
   - Atsakymus mato atitinkamo padalinio pirmininkas (nustatytas nustatymuose kaip registracijų gavėjas) ir padalinio Komunikacijos koordinatorius.
2. **Studentų atstovų registracija** (`student_rep_registration_form_id`):
   - Bendra anketa norintiems tapti studentų atstovais VU organuose. Formą ir institucijų tipus, į kuriuos galima kandidatuoti, parink skiltyje [Formų nustatymai](/sistema/nustatymai#formos).
   - Registracijas mato asmenys, turintys [studentų atstovų koordinavimo atsakomybę](/pagrindai/atsakomybes) tose institucijose arba padalinyje.

Jei naudotojas turi teisę peržiūrėti šias anketas, jos automatiškai atsiranda kairiajame naršymo meniu
kaip atskiri skyriai: **Narių registracija** (`registracija_nariai`) ir **Studentų atstovų registracija** (`registracija_atstovai`).

## Rekomendacijos {#susitarimai}

- **Aiškus adresas:** formos nuorodai parink trumpą, suprantamą kelią, pvz., `mokymai-2026`.
- **Reikalingi duomenys:** apgalvok, kokių atsakymų reikia registracijai ar apklausai. Neklausk asmens duomenų vien dėl to, kad jie gali praversti ateityje.
- **Atsakymų prieiga:** eksportuotus failus laikyk ten, kur juos gali pasiekti tik su registracijomis dirbantys žmonės.
- **Klausimų patikra:** prieš dalydamasis nuoroda peržiūrėk klausimus, atsakymų parinktis ir privalomus laukus. Gavus atsakymų, [laukų pridėjimas ir šalinimas ribojami](#vientisumas).

## Veiksmai

### Formų sąrašas ir paieška {#sarasas}

Formų sąraše (`/mano/forms`) pateikiamos visos pasiekiamos formos:

- **Paieška**: galima ieškoti pagal formos pavadinimą arba viešą kelią (`path`).
- **Rikiavimas**: formos iš pradžių rikiuojamos pagal paskutinio atnaujinimo datą (naujausios viršuje).
- **Rodikliai**: prie kiekvienos formos rodomas bendras gautų registracijų skaičius.
- **Šiukšlinė**: leidžia peržiūrėti ir atkurti pašalintas formas.

### Naujos formos kūrimas {#kurimas}

Nauja forma kuriama mygtuku **Nauja registracijos forma** sąrašo viršuje (`/mano/forms/create`):

1. **Pavadinimas** – įrašyk formos pavadinimą lietuvių ir anglų kalbomis.
2. **Aprašymas** – pateik anketos tikslą, instrukcijas pildantiems ir terminus.
3. **Formos laukai** – spausk **Pridėti lauką**, parink lauko tipą, įrašyk klausimą ir pažymėk, ar laukas privalomas.
4. **Viešas kelias** – nurodyk unikalų URL fragmentą (pvz., `mentoriai-2026`).
5. **Padalinys** – pasirink padalinį, kuriam forma priklauso.
6. Spausk **Išsaugoti**.

### Formos ir laukų redagavimas {#redagavimas}

Atidarius formos redagavimą per **Redaguoti formą** (`/mano/forms/{id}/edit`):

- **Laukų tvarkos keitimas**: spausk **Keisti tvarką** ir vilk laukus į norimą poziciją.
- **Laukų atnaujinimas**: paspaudus lauko galima patikslinti jo pavadinimą ar paaiškinimą.
- Jei forma jau turi registracijų, formos viršuje rodomas informacinis pranešimas, kad laukų pridėti ar šalinti nebegalima.

### Registracijų ir atsakymų peržiūra {#registracijos}

Paspaudus formos pavadinimo atveriamas formos puslapis (`/mano/forms/{id}`):

- Skirtuke **Registracijos** rodoma atsakymų lentelė. Stulpeliai dinamiškai sugeneruojami pagal formos klausimus.
- Pasirinkimo laukams (`enum`) viršuje rodomi greitieji filtrai, leidžiantys filtruoti atsakymus pagal pasirinktas reikšmes.
- Paspaudus bet kurios registracijos eilutės atsidaro **Registracijos peržiūros langas**, kuriame tvarkingai pateikiami visi pretendento atsakymai ir pateikimo laikas.
- Skirtuke **Laukai** galima peržiūrėti visų klausimų struktūrą ir nustatymus.
- Skirtuke **Veikla** galima rašyti vidinius komentarus tarp administratorių.

### Atsakymų eksportavimas į Excel {#eksportas}

Formos puslapio viršuje esantis mygtukas **Eksportuoti** sugeneruoja ir atsiunčia Excel (`.xlsx`) failą:

- Faile pateikiamos visos registracijos su tiksliomis datomis ir atsakymais į kiekvieną klausimą.
- Failo pavadinime automatiškai nurodomas formos pavadinimas ir eksporto laiko žyma.
- Eksporto teisę turi tik administratoriai, galintys redaguoti formą (paprastas koordinatorius, peržiūrintis atstovų registracijas tik per atsakomybę, eksportuoti negali).

### Šiukšlinė ir trynimas {#trynimas}

- Formą galima pašalinti per **⋯ → Ištrinti formą** (perkeliama į šiukšlinę).
- Ištrintą formą galima bet kada **atkurti** iš šiukšlinės sąrašo.
- **Trynimas visam laikui yra blokuojamas** (`trash.blockers.registrations`), jei formoje yra bent viena registracija. Taip apsaugoma nuo netyčinio studentų pateiktų anketų ir asmens duomenų praradimo.

## Kas ką gali {#teises}

| Veiksmas | Studentų atstovų koordinatorius | Padalinio pirmininkas | Komunikacijos koordinatorius | Centrinio biuro koordinatoriai | Superadministratorius |
|---|---|---|---|---|---|
| Matyti formų sąrašą | – | – | ✓, savo padalinio | ✓, visų padalinių | ✓ |
| Sukurti naują formą | – | – | ✓, savo padalinyje | ✓, visų padalinių | ✓ |
| Redaguoti savo padalinio formas ir laukus | – | – | ✓ | ✓ | ✓ |
| Eksportuoti atsakymus į Excel | – | – | ✓ | ✓ | ✓ |
| Matyti studentų atstovų registracijas | ✓, savo koordinuojamų organų | – | ✓, savo padalinio | ✓, visų padalinių | ✓ |
| Matyti narių registracijas | – | ✓, savo padalinio | ✓, savo padalinio | ✓, visų padalinių | ✓ |
| Ištrinti formą į šiukšlinę | – | – | ✓ | ✓ | ✓ |
| Ištrinti formą visam laikui (tik be atsakymų) | – | – | – | – | ✓ |

Centrinio biuro koordinatoriai lentelėje – **Centrinio biuro komunikacijos koordinatorius** ir
**Centrinio biuro studentų atstovų koordinatorius**.

::: tip Prieiga be formų administravimo teisės
Padalinio pirmininkas ir studentų atstovų koordinatorius gali neturėti bendros formų administravimo
rolės (`forms.*`), tačiau jie vis tiek mato savo sričių registracijas per specialiąsias teises:
pirmininkas – kaip padalinio anketų gavėjas, o koordinatorius – per pareigybės koordinavimo atsakomybę.
:::

## Pranešimai ir automatizavimas {#pranesimai}

| Kada | Kas nutinka | Kas gauna |
|---|---|---|
| Pretendentas pateikia **narių registraciją** | Išsiunčiamas patvirtinimo laiškas pretendentui (`ConfirmMemberRegistration`) | Pretendentas |
| Pretendentas pateikia **narių registraciją** | Padalinio pirmininkas gauna sistemos pranešimą ir el. laišką (`MemberRegistrationNotification`) | Padalinio pirmininkas |
| Pretendentas pateikia **studentų atstovų registraciją** | Išsiunčiamas patvirtinimo laiškas pretendentui (`ConfirmStudentRepRegistration`) | Pretendentas |
| Pretendentas pateikia **studentų atstovų registraciją** | Institucijos koordinatoriai gauna pranešimą apie naują kandidatą (`StudentRepRegistrationNotification`) | Atstovų koordinatoriai |
| Pateikiama įprasta renginio ar apklausos forma | Registracija išsaugoma duomenų bazėje; automatiniai laiškai nesiunčiami | Niekas |

Jei padalinio pirmininko pareigybė šiuo metu neužimta, nario registracijos pranešimas siunčiamas
tiesiai į institucinį pareigybės el. paštą (`@vusa.lt`). <!-- TODO: o įprastu atveju, kur siunčiama? patikrinti -->

## Techninė informacija {#technine-informacija}

Šis skyrius skirtas tiems, kurie tvarko roles ar tikrina sistemos elgseną.

### Teisės

- Formų prieigą reglamentuoja `FormPolicy`.
- `forms.read.padalinys`, `forms.create.padalinys`, `forms.update.padalinys`, `forms.delete.padalinys` suteikia prieigą prie padalinio formų.
- Specialiąsias formas (`member_registration_form_id` ir `student_rep_registration_form_id`) prižiūri `FormAccessService`:
  - Narių registraciją gali atverti naudotojai, turintys nustatymuose sukonfigūruotą gavėjo rolę (`userIsMemberRegistrationRecipient`), arba turintys `forms.read.padalinys`.
  - Studentų atstovų registraciją gali atverti naudotojai, turintys `Responsibility::StudentRepCoordination` atsakomybę, arba turintys `forms.read.padalinys`.
- Registracijų matomumą riboja `FormRegistrationVisibilityService`:
  - Narių registracijos filtruojamos pagal pretendento pasirinkto padalinio lauką (`options_model === Tenant::class`).
  - Studentų atstovų registracijos filtruojamos pagal pretendento pasirinktos institucijos lauką (`options_model === Institution::class`), atsižvelgiant į koordinatoriaus administruojamą padalinį arba specifinius institucijų tipus.
- Eksportavimas (`FormController::export`) reikalauja `update` teisės formoje.

### Kaip tai įgyvendinta

- Modeliai: `Form` (turi `translatable = ['name', 'description', 'path']`), `FormField` (laukai), `Registration` (atsakymai) ir `FieldResponse` (vieno lauko reikšmė).
- Saugus HTML: `Form::sanitizedHtmlTranslations()` valo `description` lauką per `HtmlSanitizerService` (`full` Tiptap profilį).
- Eksportas: atliekamas per `FormRegistrationsExport`, naudojant `Maatwebsite\Excel` paketą.
- Renginių ir anketų pateikimas iš viešos svetainės vykdomas per `RegistrationController::store`, kuris pagal formos laukų taisykles patikrina įvestį ir išsiunčia įvykius `MemberRegistrationCreated` arba `StudentRepRegistrationCreated`.
- Galutinio trynimo apsauga `forceDeleteBlockedReason` tikrina ryšį `registrations` (`Form::forceDeleteBlockedReason`).
