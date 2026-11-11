---
doc_status: reviewed
title: Atstovai
last_reviewed: 2026-11-11
tests:
  - tests/Feature/Admin/Dashboard/AtstovaiDashboardTest.php
  - tests/Feature/Admin/Core/AtstovavimasApiTest.php
  - tests/Feature/Api/Admin/RepStatisticsTest.php
  - tests/Feature/Api/Admin/RepMetricCadenceTest.php
  - tests/Feature/Admin/Core/RepMetricsTest.php
  - tests/Feature/System/AdminNavigationCatalogTest.php
  - resources/js/Features/Admin/Representatives/__tests__/Representatives.component.test.ts
  - resources/js/Pages/Admin/Dashboard/Components/__tests__/TenantRepMetrics.component.test.ts
  - tests/Browser/VisakOverviewPagesTest.php
---

# Atstovai

**ViSAK → Atstovai** (`/mano/dashboard/atstovavimas/atstovai`) yra studentų atstovų suvestinė
koordinatoriams. Pirmiausia rodoma, kuriems atstovams reikia dėmesio, tada – kaip aktyvūs atstovai,
kaip jiems sekasi įsivažiuoti ir kokie padalinio atstovavimo rodikliai. Visų atstovų sąrašas yra
puslapio apačioje.

<DocScreenshot name="visak-atstovai" alt="Atstovų puslapis: skaičiai, atstovai, kuriems reikia dėmesio, aktyvumo juosta, atstovavimo rodikliai ir įsivedimas" caption="Koordinatorės vaizdas: pirmiausia – kam reikia jos dėmesio." href="/mano/dashboard/atstovavimas/atstovai" />

<ChangelogNote date="2026-11-11" title="Atstovai">

Atstovų aktyvumas ir atstovavimo rodikliai perkelti iš [Padalinių](/visak/padaliniai) į šį puslapį,
o Mano kelio skiltis „Atstovai“ – čia pat. Kiekvienas atstovas turi savo puslapį.

</ChangelogNote>

## Kaip tai veikia

### Kurie atstovai rodomi {#kurie-atstovai}

Atstovas – narys, kurio dabartinės ar būsimos pareigos turi studentų atstovo atsakomybę. Puslapyje
rodomi:

- pasirinktų padalinių atstovai tose institucijose, kurios skaičiuojamos rodikliuose (pagal
  nutylėjimą – VU organai, žr. [Kurios institucijos skaičiuojamos](/visak/padaliniai#skaiciuojamos-institucijos));
- atstovai institucijų, už kurias esi atsakingas kaip studentų atstovų koordinatorius – jie rodomi visada.

Padalinius galima pasirinkti viršuje, jei matai daugiau nei vieno padalinio rodiklius. Pasirinkimas
taikomas visam puslapiui.

### Kam reikia tavo dėmesio {#demesys}

Kiekvienam atstovui nurodoma viena, svarbiausia priežastis, šia tvarka:

1. **Dar nė karto neprisijungė** prie Mano VU SA.
2. **Institucija vėluoja užfiksuoti posėdį** – bent viena jo institucija turi būseną „Vėluoja“
   (žr. [būsenas](/visak/apzvalga#busenos)).
3. **Nepradėjo būtinų Mano kelio žingsnių**, nors pareigos prasidėjo daugiau nei prieš 14 dienų.
   Ši priežastis rodoma tik tavo koordinuojamiems atstovams.
4. **Neprisijungė daugiau nei 30 d.**

Būsimi atstovai, kurių pareigos dar neprasidėjo, šiame sąraše nerodomi. Puslapyje matyti pirmi
penki atstovai; mygtukas po sąrašu atveria visus, kuriems reikia dėmesio, apatiniame sąraše.
**Parašyti** atveria laišką atstovui.

### Skaičiai ir aktyvumas {#aktyvumas}

Viršuje rodoma, kiek yra atstovų, kiek jų reikia dėmesio, kokia dalis koordinuojamų atstovų baigė
būtinus Mano kelio žingsnius ir kiek yra būsimų atstovų. Paspaudus skaičių atveriamas atitinkamas
sąrašas.

**Aktyvumo** juosta parodo, kada dabartiniai atstovai paskutinį kartą naudojosi Mano VU SA: per 7
dienas, per 30 dienų, seniau ar niekada. Paspaudus dalį sąrašas apačioje atrenkamas pagal ją,
paspaudus dar kartą – atrinkimas panaikinamas. Po juosta nurodoma, kiek atstovų per pastarąsias
90 dienų patys užfiksavo bent vieną savo institucijos posėdį. Prisijungimai nėra atstovavimo
kokybės matas – tai tik ženklas, su kuo verta pasikalbėti.

**Įsivedimas** rodo, kiek koordinuojamų atstovų dar nepradėjo, pradėjo ir baigė būtinus
[Mano kelio](/mano/kelias) žingsnius. Kitiems atstovams pažanga nerodoma.

### Atstovavimo rodikliai {#atstovavimo-rodikliai}

Rodomi trys pasirinktų padalinių rodikliai. Jie kraunami, kai rodiklių dalis pasirodo ekrane.

| Rodiklis | Kas skaičiuojama |
|---|---|
| Posėdis užfiksuotas per 7 d. | Jau įvykusių, sistemoje užregistruotų posėdžių dalis, kurių įrašas sukurtas iki posėdžio arba per 7 dienas po jo pradžios. Darbotvarkės užpildymas šiuo rodikliu nematuojamas. Neužregistruotų posėdžių sistema nežino. |
| Užpildyti darbotvarkės klausimai | Nurodytas klausimo tipas; balsavimo klausimui užpildytas pagrindinis balsavimas, o jei jo nėra – visi balsavimai. VU valdymo organuose taip pat būtinas studentų atstovo balsas ir naudos studentams įvertinimas. Nebalsuojamiems klausimams balsų nereikia. |
| Įvykdytos užduotys | Laikotarpiu sukurtų institucijos ar posėdžio užduočių dalis, kurios dabar įvykdytos. Asmeninės ir rezervacijų užduotys neįtraukiamos. |

**Dabartinė kadencija** matuojama nuo bendros organizacijos kadencijos pradžios iki šiandien. **Palyginimo kadencija** matuojama visa, nuo pradžios iki pabaigos imtinai. Lentelės antraštėse pateikiami trumpi kadencijų metai (pvz., „2026–27“), o tikslios datos ir skaičiavimo taisyklės – išskleidus **Kaip skaičiuojama**. Prie procentų pateikiami skaitiklis ir vardiklis. Bendri kelių padalinių procentai skaičiuojami iš unikalių įrašų, ne vidurkinant padalinių procentus.

Numatytoji palyginimo kadencija – ankstesnė baigta bendra kadencija. Sistemos nustatymus tvarkantis administratorius ją gali pakeisti [atstovavimo nustatymuose](/sistema/nustatymai#atstovavimas). Institucijų kadencijų išimtys šio bendro palyginimo nekeičia. Jei kadencijos nėra arba pasirinkta kadencija pašalinta, palyginimas neparenkamas tyliai; rodoma trūkstamo laikotarpio žinutė. Kai nėra ką skaičiuoti, procento vietoje rodoma „—“.

Tai **retrospektyvus** skaičiavimas pagal dabar išlikusius įrašus, ne tiksli anksčiau matyto ekrano kopija. Vėliau įvestas posėdis įtraukiamas į jo datos kadenciją; vėliau užpildytas darbotvarkės klausimas ar atlikta užduotis gali pakeisti ankstesnės kadencijos skaičius. Dabartinė kadencija dar nesibaigusi, todėl jos imtis ir metų dalis skiriasi nuo visos palyginimo kadencijos.

<DocScreenshot name="visak-atstovu-rodikliai" alt="Atstovavimo rodikliai ir įsivedimas: pasirinktų padalinių dabartinės ir palyginimo kadencijų procentai bei Mano kelio pažanga" />

### Visi atstovai {#visi-atstovai}

Sąraše galima ieškoti pagal vardą ar instituciją, atsirinkti instituciją ir aktyvumą (reikia
dėmesio, per 7 d., per 30 d., seniau, niekada, būsimi). Eilutėje matyti atstovo institucijos, kada
jis paskutinį kartą prisijungė, kiek savo institucijų posėdžių per 90 dienų užfiksavo pats ir, jei
esi jo koordinatorius, būtinų žingsnių pažanga. Rodoma po 50 atstovų; daugiau – **Rodyti daugiau**.

### Atstovo puslapis {#atstovo-puslapis}

Paspaudus atstovą atveriamas jo puslapis. Faktuose matyti, nuo kada jis eina pareigas, kada paskutinį
kartą prisijungė, kiek posėdžių užfiksavo pats, būtinų žingsnių pažanga ir atviros užduotys. Jei
atstovui reikia dėmesio, priežastis rodoma po faktais.

- **Apžvalga** – atstovo institucijos su jų būsena, paskutiniu ir kitu posėdžiu.
- **Mano kelias** – atstovo kelias taip, kaip jį mato jis pats, tik be galimybės žymėti. Šis
  skirtukas rodomas tik atstovo koordinatoriui.

**Parašyti** atveria laišką atstovui, o **⋯** – nario puslapį, jei turi teisę jį matyti.

<DocScreenshot name="visak-atstovas" alt="Atstovo puslapis: faktai, dėmesio priežastis ir institucijos su būsenomis" />

## Kas ką gali {#teises}

| Kas | Ką mato |
|---|---|
| Studentų atstovų koordinatorius, Komunikacijos koordinatorius | Savo padalinio atstovus, jų aktyvumą ir padalinio rodiklius |
| Centrinio biuro studentų atstovų koordinatorius | Visų padalinių atstovus ir rodiklius |
| Studentų atstovų koordinatorius (atsakingas už institucijas) | Savo koordinuojamus atstovus, taip pat jų Mano kelio pažangą ir kelią |
| Studentų atstovas, narys be rolės | Puslapio nemato |

Mano kelio pažanga rodoma tik koordinatoriui, atsakingam už atstovo instituciją, – vien teisė matyti
padalinio rodiklius jos neatveria.

## Pranešimai ir automatizavimas

Puslapis pranešimų nesiunčia. Dėmesio priežastys skaičiuojamos kiekvieną kartą atvėrus puslapį.

## Techninė informacija {#technine-informacija}

- Puslapis – `RepresentativesController` (`representatives.index`, `representatives.show`); duomenys –
  `RepresentativesPresenter`, sąrašas kraunamas atidėtai (`Inertia::defer`, grupė `reps`).
- Prieiga – vartai `viewRepresentatives` / `viewRepresentative`: padaliniai iš
  `AtstovavimasSettings::getVisibleTenantIds()` (`institutions.read.padalinys` arba `institutions.read.*`),
  koordinuojamos institucijos iš `ResponsibilityResolver` su `StudentRepCoordination`.
- Atstovai – dabartinės ir būsimos pareigos (`Dutiable::current()`) aktyviose pareigybėse su
  `StudentRepresentation`; padalinių institucijos parenkamos per `RepStatisticsScope::institutions()`.
- Pažanga – `ResolveLearningPath::forUsers()` tik koordinuojamiems atstovams.
- Pats užfiksuotas posėdis – posėdžio `created` įrašas veiklos žurnale, kurio autorius – atstovas.
- Atstovavimo rodikliai – `/api/v1/admin/visak/rep-metrics` (`RepMetricReportService` su padalinių filtru).
- Seni adresai `/mano/learning/reps` ir `/mano/learning/reps/{narys}` nukreipia čia.
