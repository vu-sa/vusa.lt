---
doc_status: reviewed
title: Nustatymai
area: settings
models: [Cadence]
last_reviewed: 2026-10-04
tests:
  - tests/Feature/Admin/Settings/CadenceControllerTest.php
  - tests/Feature/Admin/People/CadenceResolutionTest.php
  - tests/Feature/Admin/People/ResolveCadenceForInstitutionTest.php
  - tests/Feature/Admin/Core/AtstovavimasSettingsTest.php
  - tests/Feature/Admin/Core/SiteSettingsTest.php
  - tests/Feature/Admin/Core/DocumentSettingsTest.php
  - tests/Feature/Search/SearchExperienceTest.php
  - tests/Browser/SearchExperienceTest.php
  - tests/Feature/Public/PublicMeetingVisibilityTest.php
---

# Nustatymai

Skiltis **Nustatymai** (`/mano/settings`) skirta platformos formų, posėdžių, kadencijų ir kitiems
nustatymams tvarkyti. Ją gali atverti superadministratorius ir nariai, turintys nustatymų valdymui
pasirinktą rolę. Institucijų kadencijas taip pat galima tvarkyti turint teisę redaguoti instituciją.

## Kaip tai veikia

Nustatymai suskirstyti pagal sritis. Bendri nustatymai taikomi visai platformai, o institucijos
kadencijos leidžia aprašyti konkrečios institucijos rinkimų ciklą.

## Nustatymų sritys {#sritys}

### Kadencijos {#kadencijos}

**Kadencija** – laikotarpis, kuriam renkami nariai, dažniausiai nuo liepos 1 d. iki kitų metų
birželio 30 d. Kadencijos tvarkomos adresu `/mano/settings/cadences`.

- **Bendros VU SA kadencijos** galioja visoms institucijoms. Jas kurti, keisti ir trinti gali
  superadministratorius ir narys su nustatymų valdymui pasirinkta role (**Nustatymai → Autorizacijos
  nustatymai**). Dvi bendros kadencijos negali prasidėti tą pačią dieną.
- **Institucijos kadencijos** – kai institucija renkama kitu ritmu. Jas savo padalinio institucijoms
  kuria **Komunikacijos koordinatorius** ir **Studentų atstovų koordinatorius** (Centrinio biuro
  koordinatoriai – visoms). Kitų padalinių institucijų ir bendrų kadencijų jie keisti negali. Jei
  institucija turi bent vieną savą kadenciją, bendros jai **visai nebetaikomos**.
- Kadencijos ribą galima susieti su **posėdžiu** (pvz., rinkimų konferencija): tada jos data imama iš
  posėdžio ir pasikeičia, kai perkeliamas posėdis.
- Numatytosios pradžios ir pabaigos dienos (mėnuo ir diena) nurodomos tame pačiame puslapyje ir
  siūlomos kuriant naują kadenciją.
- Dvejus kalendorinius metus apimanti kadencija vadinama abiem metais (pvz., „2025–2026“).

Kadencijos rodomos [Laikotarpių tvarkyklėje](/visak/pareigybiu-laikotarpiai#kadencijos) ir padeda
derinti pareigybių laikotarpių datas. [Pareigybių atnaujinimo vedlys](/organizacija/pareigybiu-atnaujinimas#zingsnis-3)
siūlo atskirą liepos 1 d. reikšmę; ji nėra imama iš šių kadencijų.

### Autorizacijos nustatymai {#autorizacija}

Adresas: `/mano/settings/authorization`. Šį puslapį gali atverti ir keisti **tik superadministratorius**.

Čia pasirenkama, kuri rolė įgyja teisę valdyti sistemos nustatymus.
Pati superadministratoriaus rolė į sąrašą neįtraukiama, nes jai nustatymų prieiga priklauso visada.

### Formų nustatymai {#formos}

Adresas: `/mano/settings/forms`. Čia nurodomos pagrindinės platformos registracijos anketos:

- **Narių registracijos forma**: forma, kuria viešai registruojasi nauji VU SA nariai.
- **Narių registracijos gavėjo rolė**: rolė, kurios pareigybių pašto dėžutėms siunčiami pranešimai apie
  naujas narių registracijas atitinkamame padalinyje.
- **Studentų atstovų registracijos forma**: forma, kuria studentai registruojasi tapti atstovais VU organuose.
- **Institucijų tipai atstovų registracijai**: institucijų tipai, kuriems leidžiama registruotis kaip
  studentų atstovui.

### Posėdžių nustatymai {#posedziai}

Adresas: `/mano/settings/meetings`. Čia nustatomas posėdžių atvaizdavimas ir viešumas:

- **Viešų posėdžių institucijų tipai**: institucijų tipai, kurių posėdžių puslapiai yra viešai
  prieinami ir indeksuojami viešojoje paieškoje.
- **Neįtraukiami institucijų tipai**: tipai, kurių institucijos neįtraukiamos į atstovavimo suvestinių rodiklius.

### Atstovavimo nustatymai {#atstovavimas}

Adresas: `/mano/settings/atstovavimas`. Čia nurodomas **pagrindinis studentų atstovavimo institucijų tipas**,
nuo kurio pradedama atstovavimo institucijų hierarchija.

### Dokumentų nustatymai {#dokumentai}

Adresas: `/mano/settings/documents`. Čia pažymimi **svarbūs dokumentų turinio tipai**. Šie tipai tiesiogiai veikia dviejose vietose:

- **Viešojoje dokumentų paieškoje**: jie siūlomi kaip greitieji filtrai, padedantys
  atsirinkti svarbiausius dokumentus (pvz., įstatus, darbo reglamentus). Vienodai aktualūs
  šių tipų rezultatai rodomi aukščiau. Be paieškos frazės bendras sąrašas rodomas nuo naujausių.
- **Vidiniame dokumentų sąraše** (`/mano/documents`): jie siūlomi kaip greitojo filtravimo parinktys.

<ChangelogNote version="v3.0" date="2026-10-02" title="Rekomenduojami dokumentai paieškoje">

Tame pačiame puslapyje pridėk rekomendaciją ir pasirink dokumentą (paspaudęs pavadinimą, jį
pakeisi). Kiekviena rekomendacija rodoma dviem atskirais atvejais:

- **Rodyti ieškant** – frazės per kablelį, pvz., VU SA įstatams – **VU SA įstatai**.
  Rekomendacija rodoma, kai visi įvesti žodžiai yra frazėje: **įstat**, **įstatų** ar
  **SA įstatus** ją suaktyvina, o **pakeisti įstatai** – ne, nes žodžio **pakeisti** frazėje nėra;
  tokiai užklausai pridėk atskirą frazę. Lietuviškos žodžio formos ir pradėtas rašyti žodis
  atpažįstami automatiškai. Be frazių rekomendacija paieškos metu nerodoma.
- **Rodyti ir tuščioje paieškoje** – rekomendacija rodoma atidarius dokumentų paiešką, kol nieko
  neįvesta (naujoms rekomendacijoms pažymėta).

**Laikinai išjungti** paslepia rekomendaciją jos neištrinant. Rodyklėmis pakeisk
rekomendacijų eilę. Išsaugojus ji rodoma viešos paieškos skiltyje
**Rekomenduojami dokumentai**, nesikartodama bendrame sąraše. Filtrai galioja ir rekomendacijoms;
neaktyvūs ar pašalinti dokumentai nerodomi. Pradiniame rodinyje rekomendacijos yra virš
nuo naujausių rikiuojamo bendro sąrašo; **Naujausi pirmi** pažymėta ir rikiavimo valdiklyje.
Įvedus paiešką sąrašas savaime rikiuojamas **Pagal aktualumą** (ši parinktis siūloma tik tada),
nebent pasirinkai rikiavimą pagal datą. Rekomendacijos lieka virš bendro sąrašo ir rikiuojant
**Naujausi pirmi**. Pasirinkus **Seniausi pirmi**
rekomendacijos nerodomos. Abu rikiavimai pagal datą netaiko svarbių tipų pirmumo.

</ChangelogNote>

### Svetainės nustatymai {#svetaine}

Adresas: `/mano/settings/site`. Čia nurodomi privatumo politikos puslapiai lietuvių ir anglų kalbomis.
Puslapių pasirinkimo laukas ne superadministratoriams rodo tik jų pačių padalinio puslapius.

## Veiksmai

1. Atverk **Nustatymai** ir pasirink reikiamą sritį.
2. Pakeisk laukų reikšmes ir paspausk **Išsaugoti**.
3. Jei forma rodo klaidas, pataisyk nurodytus laukus ir išsaugok dar kartą.

## Kas ką gali {#teises}

| Veiksmas | Komunikacijos ar studentų atstovų koordinatorius | Narys su nustatymų valdymo role | Superadministratorius |
|---|---|---|---|
| Keisti autorizacijos nustatymus | – | – | ✓ |
| Keisti formų, posėdžių, dokumentų, atstovavimo ir svetainės nustatymus | – | ✓ | ✓ |
| Kurti ir keisti bendras VU SA kadencijas | – | ✓ | ✓ |
| Kurti ir keisti savo padalinio institucijų kadencijas | ✓ Savo padalinyje | Tik jei gali redaguoti instituciją | ✓ |

Lentelė rodo įprastą koordinatoriaus prieigą. Jei jo rolė pasirenkama nustatymams valdyti, jis įgyja
ir atitinkamą nustatymų prieigą.

Superadministratorius gali tvarkyti visus nustatymus. Institucijų kadencijoms kurti pakanka
teisės redaguoti instituciją. Vien nustatymų valdymo rolės tam nepakanka.

## Pranešimai ir automatizavimas {#pranesimai}

- **Navigacijos atnaujinimas**: pakeitus formų nustatymus, sistema išvalo visų naudotojų navigacijos
  talpyklą, kad nuorodos į registracijos formas kairėje juostoje atsinaujintų.
- **Paieškos atnaujinimas**: pakeitus viešų posėdžių tipus, foninė užduotis atnaujina
  viešųjų posėdžių ir institucijų paieškos indeksus.
- **Susietų posėdžių sinchronizavimas**: pakeitus su kadencija susieto posėdžio laiką, automatiškai
  perskaičiuojamos ir atnaujinamos su juo susietos kadencijos datos.

## Techninė informacija {#technine-informacija}

- Nustatymų valdiklis yra `SettingsController`, kadencijų – `CadenceController`.
- Nustatymai saugomi `spatie/laravel-settings` klasėse: `SettingsSettings`, `CadenceSettings`, `FormSettings`, `MeetingSettings`, `AtstovavimasSettings`, `DocumentSettings`, `SiteSettings`.
- Bendrą prieigą prie nustatymų tikrina `SettingsSettings::canUserManageSettings()`.
- Institucijos kadencijos prieigą tikrina `CadencePolicy` per `InstitutionPolicy::update` (`institutions.update` su atitinkama apimtimi).
- Nustatymų valdymo rolę saugo `settings_manager_role_id`, atstovavimo hierarchijos pradžią – `student_rep_root_type_id`, dokumentų filtrus – `important_content_types`.
- Viešumą tikrina `Meeting::isPubliclyVisible()`; viešų tipų pakeitimai atnaujina `PublicMeeting` ir `PublicInstitution` indeksus. Su posėdžiu susietas kadencijų datas atnaujina `syncAnchoredCadences`.
- Kuri kadencija taikoma institucijai, sprendžia `ResolveCadenceForInstitution` (sava kadencija atjungia bendrąsias), o pareigybėms – `ResolveCadenceForDuty`.
- Testai: `CadenceControllerTest`, `CadenceResolutionTest`, `ResolveCadenceForInstitutionTest`, `AtstovavimasSettingsTest`, `SiteSettingsTest`, `PublicMeetingVisibilityTest`.
