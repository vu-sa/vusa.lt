---
doc_status: reviewed
title: Problemos
area: problems
models: [Problem, ProblemCategory]
last_reviewed: 2026-09-30
tests:
  - tests/Feature/Admin/Problems/ProblemControllerTest.php
  - tests/Feature/Admin/Problems/AgendaItemProblemControllerTest.php
  - tests/Feature/Admin/Problems/ProblemCategoryControllerTest.php
  - tests/Feature/Api/Admin/ProblemApiControllerTest.php
  - tests/Feature/Admin/Management/InstitutionControllerTest.php
  - tests/Feature/Permissions/StudentRepresentativeRoleTest.php
  - tests/Feature/Permissions/BaselineAccessTest.php
  - tests/Feature/Seeders/RoleSeedersTest.php
  - tests/Feature/System/AdminNavigationCatalogTest.php
  - resources/js/Components/AdminForms/__tests__/ProblemForm.component.test.ts
  - resources/js/Components/Problems/__tests__/ProblemSummaryList.component.test.ts
  - resources/js/Components/Problems/__tests__/ProblemLinkSheet.component.test.ts
  - resources/js/Features/Admin/ProblemCategories/__tests__/ProblemCategorySheetForm.component.test.ts
  - tests/Browser/ProblemPagesTest.php
---

# Problemos

Problema – studentams aktualus trukdis, su kuriuo susidūrė studentai ar atstovai: studijų proceso,
komunikacijos, išteklių ar kitos srities. Užregistruota problema lieka bendroje žinių bazėje: kiti
padaliniai mato, kas jau spręsta ir kaip, o ta pati problema nesprendžiama du kartus.

Problemų sąrašas pasiekiamas adresu `/mano/problems`, o kiekviena problema turi savo puslapį
`/mano/problems/{id}`.

<DocScreenshot name="problem-record" alt="Problemos puslapis: būsena, kategorijos, institucija, įvykio data, trukmė, atsakingas asmuo ir skirtukai Aprašymas, Atlikti žingsniai, Sprendimas, Svarstyta posėdžiuose" caption="Vykdoma problema, kurią atstovė kėlė fakulteto tarybos posėdyje." />

## Kaip tai veikia

### Ką sudaro problema

| Laukas | Privaloma | Pastabos |
|---|---|---|
| Pavadinimas | Taip, bent viena kalba | Iki 255 simbolių |
| Aprašymas | Taip, bent viena kalba | Kas, kur ir kada įvyko, ką tai paveikė |
| Padalinys | Taip | Tik tas, kuriame gali kelti problemas |
| Būsena | Taip | Nauja problema – **Atvira** |
| Įvykio data | Taip | Numatytoji – šiandien |
| Atlikti žingsniai, Sprendimas | Ne | Galima papildyti vėliau |
| Atsakingas asmuo, išsprendimo data | Ne | Žr. [Būsenos](#busenos) ir [Atsakingas asmuo](#atsakingas) |
| Kategorijos, institucijos | Ne | Institucijos – tik problemos padalinio |

<DocScreenshot name="problem-form" alt="Naujos problemos forma: pavadinimas, aprašymas, atlikti žingsniai, padalinys, būsena, atsakingas asmuo, datos, kategorijos ir institucijos" caption="Problemos forma. Lietuviškas ir angliškas tekstas pildomi perjungus kalbą." />

### Būsenos {#busenos}

| Būsena | Reiškia |
|---|---|
| **Atvira** | Problema užregistruota, bet dar nespręsta |
| **Vykdoma** | Problema šiuo metu sprendžiama |
| **Išspręsta** | Problema išspręsta |

Išsprendimo data seka būseną: pažymėjus problemą **Išspręsta** be datos, įrašoma šiandienos data,
o grąžinus į **Atvira** ar **Vykdoma** – data išvaloma. Tai galioja ir formoje, ir būsenos juostoje.
Išsprendimo data negali būti ankstesnė už įvykio datą.

Problemos puslapyje **Trukmė** rodo, kiek dienų problema atvira arba per kiek dienų ji išspręsta.

### Kategorijos {#kategorijos}

Kategorijos padeda rasti panašias problemas: pagal jas filtruojamas sąrašas. Jos **bendros visiems
padaliniams**, pvz., „Komunikacija“, „Procesai“, „Administracinės problemos“. Kategorijas tvarko
centrinio biuro koordinatoriai skiltyje **ViSAK → Problemų kategorijos**
(`/mano/problemCategories`):

- Naujai kategorijai reikia lietuviško pavadinimo, aprašymas neprivalomas. Aprašymas rodomas
  problemos formoje, kai renkamasi kategorija.
- Kategoriją galima pervadinti, jos techninė žymė nesikeičia.
- Kategorijos, priskirtos bent vienai problemai (net ištrintai), **ištrinti negalima** – sąraše
  matyti, kiek problemų ją naudoja.

<DocScreenshot name="problem-categories" alt="Problemų kategorijų sąrašas su aprašymais, problemų skaičiumi ir veiksmais" caption="Kategorijos, kurias naudoja problemos, neturi trynimo mygtuko." />

### Institucijos ir posėdžiai {#posedziai}

Problemą galima susieti su institucijomis, su kuriomis ji susijusi, ir su **darbotvarkės
klausimais**, kuriuose atstovai ją kėlė. Taip matyti, kur problema svarstyta ir kas nuspręsta.

- Susieti galima tik **problemos padalinio** institucijas.
- Institucijos puslapyje atsiranda skirtukas **Problemos** (rodomas, kai jų yra): pirmiau
  neišspręstos, paskui išspręstos.
- Darbotvarkės klausimą su problema susieja tas, kas gali redaguoti klausimą (žr.
  [Veiksmai](#susieti)). Tada problemai priskiriamos ir posėdžio institucijos, jei jos yra
  problemos padalinyje.
- Problemos puslapyje skirtukas **Svarstyta posėdžiuose** rodo klausimus su posėdžio data ir
  institucija. Klausimai iš posėdžių, kurių tu atidaryti negali, nerodomi.

<ChangelogNote version="v2.28" date="2026-09-27" title="Problemos susietos su posėdžiais">

Nuo v2.28 problemą galima susieti su darbotvarkės klausimu, o institucijos puslapyje matyti
jos problemos.

</ChangelogNote>

### Atsakingas asmuo {#atsakingas}

**Atsakingas asmuo** – narys, kuris rūpinasi problemos sprendimu. Jį galima pasirinkti formoje
(ieškoma pagal vardą, bent 2 simboliai). Sąrašo greitasis filtras **Man priskirtos** rodo
problemas, kurių atsakingas asmuo esi tu.

### Kas mato problemas

Problemas mato **kiekvienas narys, net be rolės**: visų padalinių problemos yra bendra žinių bazė.
Sąraše visi padaliniai rodomi kartu, o savo padalinio problemas atsirenki greituoju filtru.

## Veiksmai

### Užregistruoti problemą

Problemą užregistruoti gali:

- per **+ Sukurti → Nauja problema** arba sąrašo mygtuką **Nauja problema**;
- iš institucijos puslapio skirtuko **Problemos** – forma atsidaro jau su institucijos padaliniu ir
  pačia institucija.

Užregistravusio nario vardas lieka lauke **Sukūrė**.

### Keisti būseną {#busena}

Problemos puslapyje būsenos juostoje spausk norimą būseną arba **⋯ → Pažymėti kaip**. Išsprendimo
data užpildoma ar išvaloma automatiškai (žr. [Būsenos](#busenos)).

### Susieti su darbotvarkės klausimu {#susieti}

Darbotvarkės klausimo puslapyje, skiltyje **Susijusios problemos**, spausk **Susieti problemą** ir
pasirink problemą. Sąraše rodomos tik atviros ir vykdomos problemos, jas galima ieškoti pagal
pavadinimą ar aprašymą. Susietą problemą atsieja mygtukas **Atsieti**.

### Filtruoti ir ieškoti {#filtrai}

<DocScreenshot name="problems-index" alt="Problemų sąrašas su greitaisiais filtrais, paieška ir pažymėtos problemos peržiūra" caption="Sąrašas: kairėje problemos, dešinėje pažymėtosios peržiūra." />

- Paieška ieško pavadinime ir aprašyme.
- Greitieji filtrai: **Atviros ir vykdomos**, **Mano padalinio**, **Man priskirtos**, **Mano
  sukurtos**.
- Filtrai: būsena, kategorija, institucija, padalinys.
- Rikiavimas pagal įvykio datą (naujausios arba seniausios).
- Mygtukas **Redaguoti** prie problemos rodomas tik tada, kai tą problemą gali redaguoti.

### Ištrinti ir atkurti

**Ištrinta** problema patenka į [šiukšlinę](/pagrindai/platforma#siuksline), iš kurios ją galima
**atkurti**. Galutinai ištrinti problemą gali tik super administratorius.

## Kas ką gali {#teises}

Problemas kelia ir tvarko **studentų atstovai** ir **koordinatoriai**. Koordinatoriai rolę
**Problemų redaktorius** gauna automatiškai pagal pareigybės tipą „Koordinatorius (-ė)“ (žr.
[Rolės pagal pareigybės tipą](/pagrindai/teises#roles-pagal-pareigybes-tipa)).

| Veiksmas | Bet kuris narys | Studentų atstovas, Problemų redaktorius | Studentų atstovų koordinatorius, Komunikacijos koordinatorius | Centrinio biuro komunikacijos koordinatorius | Centrinio biuro studentų atstovų koordinatorius |
|---|---|---|---|---|---|
| Matyti visų padalinių problemas | ✓ | ✓ | ✓ | ✓ | ✓ |
| Užregistruoti problemą | – | ✓, savo padalinyje | ✓, savo padalinyje | ✓ | ✓ |
| Redaguoti ir keisti būseną | – | ✓, **bet kurią** savo padalinio | ✓, savo padalinio | ✓ | ✓ |
| Ištrinti ir atkurti | – | – | ✓, savo padalinio | – | ✓ |
| Tvarkyti kategorijas | – | – | – | ✓, be trynimo | ✓ |
| Galutinai ištrinti | – | – | – | – | – |

Galutinai ištrinti gali tik super administratorius. Susieti problemą su darbotvarkės klausimu gali
tas, kas gali redaguoti tą klausimą (žr. [Darbotvarkės klausimai](/visak/darbotvarkes-klausimai#teises)).

::: info Studentų atstovas redaguoja visas padalinio problemas
Kol kas studentų atstovas gali keisti ne tik savo iškeltas, bet ir kolegų to paties padalinio
problemas. Kitų padalinių problemų keisti negali.
:::

## Pranešimai ir automatizavimas {#pranesimai}

Problemos pačios pranešimų nesiunčia: paskyrus atsakingą asmenį ar pakeitus būseną, niekas
pranešimo negauna. Automatiškai tik užpildoma ar išvaloma išsprendimo data (žr.
[Būsenos](#busenos)). Aptarti problemą su kolegomis galima jos puslapio skiltyje **Veikla** (žr.
[Komentarai](/visak/komentarai)).

## Susitarimai {#susitarimai}

::: tip Kad žinių bazė būtų naudinga
- Registruok problemą, kai ji pasikartoja ar liečia daugiau nei vieną studentą, net jei dar
  nežinai, kaip ją spręsti.
- Kai problemą keli posėdyje, susiek ją su darbotvarkės klausimu – taip kiti matys, kur ji
  svarstyta.
- Išsprendęs problemą, aprašyk **sprendimą**: būtent jis padeda kitiems padaliniams.
:::

## Techninė informacija {#technine-informacija}

### Teisės

- Skaityti: `ProblemPolicy::viewAny` ir `view` leidžiami visiems; `problems.read.*` nebenaudojama
  (`BaselineAccess`), sąrašas nefiltruojamas pagal teises.
- Rašyti: `problems.{create|update|delete}.{padalinys|*}`; `restore` tikrina `delete`.
  `problems.forceDelete.*` neturi nė viena rolė.
- Rolės: Studentų atstovas ir Problemų redaktorius – `create|update.padalinys`; Studentų atstovų
  koordinatorius ir Komunikacijos koordinatorius – `create|update|delete.padalinys`; Centrinio
  biuro komunikacijos koordinatorius – `create|update.*`; Centrinio biuro studentų atstovų
  koordinatorius – `create|update|delete.*`.
- Kategorijos: `ProblemCategoryPolicy` – tvarkyti reikia `problems.update.*`, trinti –
  `problems.delete.*`. Meniu skiltis rodoma pagal `problems.update.*`.
- Susiejimas su darbotvarkės klausimu: `StoreAgendaItemProblemRequest` tikrina `update` klausimui.

### Kaip tai įgyvendinta

- Sąrašas: `BuildProblemIndexQuery` (filtrai `status`, `category`, `institution`, `tenant.id`,
  `created_by`, `responsible_user_id`); kiekviena eilutė gauna `can_update`.
- Išsprendimo data: `Problem::booted()` (`saving`) derina `resolved_at` su `status`.
- Institucijos: `ProblemRequest` leidžia tik `tenant_id` padalinio institucijas.
- Ryšiai: `institution_problem`, `agenda_item_problem`; `AgendaItemProblemController`,
  `ProblemSummaryResource` (institucijos skirtukas, darbotvarkės klausimas).
- Kategorijos: `ProblemCategoryController`; numatytosios – `ProblemCategorySeeder`.
