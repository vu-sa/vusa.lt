---
doc_status: reviewed
last_reviewed: 2026-10-04
tests:
  - tests/Feature/Search/SearchExperienceTest.php
  - tests/Feature/Api/Admin/ContentEditorTest.php
  - tests/Browser/RichContentFullscreenEditorTest.php
  - tests/Feature/Admin/Content/PageControllerTest.php
title: Puslapiai
area: pages
models: [Page, Tag]
---

# Puslapiai

Puslapiai – pagrindiniai ilgalaikio svetainės turinio vienetai su tekstine, vaizdine ir interaktyvia informacija (aprašymai, kontaktai, gairės, programos, DUK). Juos kuria ir atnaujina komunikacijos koordinatoriai bei padalinių puslapių redaktoriai.

Skiltis pasiekiama adresu `/mano/pages`.

## Rekomendacijos {#rekomendacijos}

::: tip
- **Puslapis ar naujiena:** ilgalaikei informacijai, pvz., stipendijų ar atstovavimo tvarkai, kurk puslapį. Laikiniems pranešimams ir ataskaitoms naudok [naujienas](/svetaine/naujienos).
- **Antraštės:** pagrindinius teksto skyrius žymėk antrojo lygio antraštėmis (H2), poskyrius – trečiojo (H3). Pirmąjį lygį (H1) palik puslapio pavadinimui.
- **Skaitomas tekstas:** rašyk trumpomis pastraipomis, o veiksmus ar lygiaverčius punktus pateik sąrašu. Patikrink, kaip tekstas atrodo telefone.
- **Iliustracijos:** naudok turinį papildančius vaizdus, o svarbią informaciją pateik ir tekstu.
:::

## Kaip tai veikia

Paieška randa išsaugotą pavadinimą, aprašymą, žymas ir turinio tekstą. Ištrauka po rezultato pavadinimu padeda suprasti atitikimą. Viešoje paieškoje išlieka tik viešai skelbiami įrašai. [Paieška ir filtrų skaičiai](/pagrindai/platforma#paieska-ir-pasirinkimas).


### Struktūra ir hierarchija

Puslapiai organizuojami medžio principu:

- **Puslapiai ir polapiai:** puslapį gali priskirti kitam puslapiui ir taip sukurti turinio hierarchiją, pvz., puslapiui „Atstovavimas“ priskirti polapius „Studijų programų komitetai“ ir „Ginčų komisija“.
- **Nuorodų istorija:** puslapis turi viešą adresą. Pakeitus šį adresą, ankstesnė nuoroda nukreipia lankytoją į naująjį puslapio adresą.

### Būsenos

| Būsena | Reikšmė | Matomumas |
|---|---|---|
| **Juodraštis** (`draft`) | Puslapis kuriamas ar atnaujinamas. | Matomas tik sistemoje prisijungusiems redaktoriams. |
| **Paskelbta** (`published`) | Puslapis paruoštas skaitytojams. | Matomas viešoje svetainėje pagal tiesioginę nuorodą ir paieškoje. |

### Temos {#kategorijos}

Puslapius su susijusiomis naujienomis ir renginiais gali susieti žymomis. Žyma, turinti
temos puslapį, vienoje vietoje pateikia su ja susietą viešą turinį.
Žymų kūrimas ir temos puslapio parinktis aprašyti [Žymų skiltyje](/svetaine/zymos#temos-puslapis).

## Turinio redagavimas {#turinio-redagavimas}

<ChangelogNote version="v2.0" date="2026-09-05" title="Turinį redaguok puslapio vaizde">

Vizualus redaktorius leidžia keisti turinio blokus jų peržiūroje. Viso ekrano režimas skirtas turiniui; pavadinimą, viršelį ir kitus įrašo duomenis tvarkyk formoje.

</ChangelogNote>

Skaitytojams redaktorius neįkeliamas. Turėdamas puslapio redagavimo teisę gali atverti turinio redaktorių ir keisti tekstą tiesiogiai turinio bloke. Viso ekrano režime redaguoji tik turinio blokus; pavadinimas, viršelis ir puslapio nustatymai lieka formoje. **Turinio struktūra** leidžia pereiti prie bloko, skyriaus ar antraštės. **Išsaugoti** išsaugo visą formą, taip pat pakeistus aprašymą, viršelį ir svarbiausius punktus.

Puslapio formoje atverk **Redaguoti turinį**. Blokų pasirinkimo lange pasirink kategoriją arba ieškok pagal pavadinimą. Kompiuteryje bloką įterpia jo eilutė; telefone pirmiausia pamatysi peržiūrą, tada spausk **Pridėti šį bloką**. Peržiūros variantus gali pakeisti prieš įterpdamas.

Nuotraukų tinklelyje ir galerijoje paveikslėlį gali pakeisti paspaudęs jo peržiūrą. Kiekvienos nuotraukos meniu gali pakeisti vietą sąraše, nustatyti fokusavimo tašką ar pašalinti nuotrauką. Galerijos nustatymuose pasirink stulpelių skaičių, tarpus ir ar įjungti viso dydžio peržiūrą. Telefone nuotraukos nustatymai atsidaro tame pačiame redaktoriuje; grįžimo mygtukas parveda prie nuotraukų.

Tvarkaraščio eilutes gali įrašyti ranka arba importuoti iš posėdžio. Importas nukopijuoja dabartinę darbotvarkę į puslapio turinį; vėlesni posėdžio pakeitimai tvarkaraščio nekeičia. Socialinio įrašo bloke įklijuok Facebook arba Instagram nuorodą; redaktorius parodys, ar ją atpažino, ir pateiks peržiūrą. Teksto laukelio bloke atsakymus gali peržiūrėti, eksportuoti arba po patvirtinimo ištrinti.

## Atkūrimo kopijos {#atkurimas}

<ChangelogNote version="v3.0" date="2026-10-02" title="Privatūs pakeitimai ir aiškesnis atkūrimas">

Automatinė atkūrimo kopija padeda tęsti darbą, bet nepakeičia paskelbto puslapio. Įrašą atnaujini tik paspaudęs **Išsaugoti**.

</ChangelogNote>

Pakeitimus paskelbi arba išsaugai kaip juodraštį paspaudęs **Išsaugoti**. Kol redaguoji, privati atkūrimo kopija saugoma šiame įrenginyje ir serveryje. Jos nemato nei lankytojai, nei kiti redaktoriai; automatinis kopijos saugojimas nekeičia išsaugoto puslapio.

Grįžęs į redagavimą gali pamatyti **Tęsk nebaigtus pakeitimus**. Pasirink įrenginio arba serverio kopiją ir spausk **Atkurti kopiją**. Jei kopijos skiriasi, jos rodomos atskirai. **Atsisakyti atkūrimo kopijų** prašo patvirtinimo ir nepakeičia išsaugoto puslapio. Neatnaujintos kopijos saugomos iki 30 dienų.

Jei neveikia ryšys, tęsk darbą šiame įrenginyje. Prisijungus serverio saugojimas bandomas vėl. Jei naršyklė negali saugoti kopijų, formoje matai paaiškinimą – palik ją atvertą, kol pavyks išsaugoti. Nepavykus išsaugoti pakeitimai lieka formoje.

Jei kitas redaktorius išsaugojo naujesnę puslapio versiją, tavo pakeitimai jos neperrašo. Spausk **Peržiūrėti dabartinę versiją**, pasirink, kurią kopiją tęsti, ir išsaugok dar kartą.

## Kalbų versijos {#kalbu-versijos}

<ChangelogNote version="v3.0" date="2026-10-02" title="Abi kalbas redaguok kartu">

Kalbų versijas gali palyginti ir redaguoti viename lange. Kiekviena išsaugoma ir skelbiama atskirai; kitos kalbos versija nesukuriama ar nepaskelbiama savaime.

</ChangelogNote>

<DocScreenshot name="page-form" alt="Kalbų versijos: lietuviškas ir angliškas puslapis greta, kiekvienas su savo pavadinimu, paskelbimo žyma ir mygtuku Išsaugoti" caption="Palyginimo lange kiekviena kalba išsaugoma atskirai." />

Skiltyje **Kalba** pasirink tos pačios informacijos įrašą kita kalba. Paieška iš pradžių rodo dabartinio padalinio priešingos kalbos įrašus. Pasirinktą versiją gali atverti atskirai arba spausti **Palyginti ir redaguoti**. Kompiuteryje abi versijos rodomos greta; telefone persijunk **LT** ir **EN**. Kiekvieną versiją išsaugai ir paskelbi atskirai.

Jei kitos versijos nėra ir gali kurti puslapius, spausk **Sukurti versiją kita kalba**. Nauja versija atsidaro tuščia ir iš pradžių yra juodraštis; padalinį, žymas, viršelį ir rodymo nustatymus ji perima iš originalo. Jei patogiau versti ant esamo teksto, spausk **Nukopijuoti LT turinį** (arba EN) – įkeliamas dabartinis pavadinimas, tekstas ir blokai, įskaitant dar neišsaugotus pakeitimus. Jei originalas dar nesukurtas, pirmiausia išsaugok jį, tada kitą versiją.

Susiejimas išsaugomas kartu su forma. Jei pasirinkta versija jau susieta su kitu įrašu, prieš pakeisdamas ryšį matai paveikiamus įrašus ir turi patvirtinti pakeitimą. Turi galėti redaguoti visus paveikiamus įrašus. Ryšį pašalini pasirinkimo lange spausdamas **Atsieti kalbų versijas** – jis panaikinamas išsaugojus.

### Automatiškai pildomi blokai {#automatiniai-blokai}

Naujienų, renginių, nuorodų ir institucijų sąrašų blokai pasipildo patys. Paskelbta naujiena, renginys ar pakeistas puslapis juose matomi iš karto. Praėjęs renginys iš artėjančių sąrašo dingsta per 10 minučių.

## Veiksmai

### Puslapių sąrašas ir paieška

Sąraše (`/mano/pages`) gali ieškoti puslapių pagal pavadinimą:

- **Greitieji filtrai:** gali vienu paspaudimu atsirinkti paskelbtus puslapius arba juodraščius.
- **Rūšiavimas:** pagal atnaujinimo datą, sukūrimo laiką ar pavadinimą.

### Naujo puslapio kūrimas

Puslapio viršuje spausk **Naujas puslapis** (arba eik adresu `/mano/pages/create`):

1. **Padalinys** – pasirink atstovaujamą padalinį.
2. **Pavadinimas** – įrašyk aiškų puslapio pavadinimą.
3. **Tėvinis puslapis** – jei kuriamas puslapis yra kito puslapio polapis, nurodyk tėvinį įrašą.
4. **Viršelis** – parink reprezentacinę nuotrauką.
5. **Turinys** – atverk redaktorių ir suformuok turinio blokus.
6. **Būsena** – dešinėje nustatyk „Paskelbta“ arba palik „Juodraštis“.
7. Spausk **Išsaugoti**.

### Veiksmai su keliais įrašais

Sąraše pažymėjęs kelis puslapius varnelėmis, viršutinėje veiksmų juostoje gali atlikti grupinius veiksmus:

- **Keisti būseną:** pakeisti visų pažymėtų puslapių būseną į „Paskelbta“ arba „Juodraštis“.
- **Ištrinti:** perkelti pažymėtus puslapius į šiukšlinę.

### Šalinimas ir atkūrimas

- **Perkėlimas į šiukšlinę:** puslapio meniu pasirink **Ištrinti**.
- **Šiukšlinės peržiūra ir atkūrimas:** šiukšlinės rodinyje spausk **Atkurti**. Puslapis vėl atsiranda aktyvių puslapių sąraše.
- **Ištrinti visam laikui:** galutinai pašalina įrašą ir jo turinio blokus.

## Kas ką gali {#teises}

| Veiksmas | Narys be rolės | Padalinio puslapių redaktorius | Komunikacijos koordinatorius | Centrinio biuro komunikacijos koordinatorius |
|---|---|---|---|---|
| Matyti sąrašą | – | ✓ (savo padalinio) | ✓ (savo padalinio) | ✓ (visus) |
| Redaguoti esamus puslapius | – | ✓ (savo padalinio) | ✓ (savo padalinio) | ✓ (visus) |
| Kurti naujus puslapius | – | – | ✓ (savo padaliniui) | ✓ (visiems) |
| Trinti puslapius į šiukšlinę | – | – | ✓ (savo padalinio) | ✓ (visus) |
| Keisti tėvinį puslapį / struktūrą | – | – | ✓ (savo padalinio) | ✓ (visų) |
| Atkurti / trinti visam laikui | – | – | ✓ (savo padalinio) | ✓ (visų) |

::: tip Redaktoriaus rolė
Rolė **Padalinio puslapių redaktorius** skirta komandos nariams ar koordinatoriams, kurie pildo ar atnaujina esamų padalinio puslapių tekstus (pvz., kontaktus ar programų aprašus), tačiau patys nekuria naujų skilčių ir nekeičia svetainės medžio struktūros.
:::

## Pranešimai ir automatizavimas {#pranesimai}

- **Automatinis juodraščių saugojimas:** redaguojant tekstą, privačios kopijos saugomos naršyklėje ir serveryje. Jos padeda atkurti nebaigtą darbą. Jei kopijos išsaugoti nepavyksta, formoje rodomas perspėjimas.
- **Kanoniniai nukreipimai (301):** pakeitus puslapio adresą, ankstesni adresai lieka nukreipti į naująjį adresą, todėl išorinės nuorodos ar paieškos sistemų indeksai nesugenda.
- **Dinaminiai blokai:** automatiškai atsinaujina naujienų ir renginių sąrašai, įtraukti į puslapį.

## Techninė informacija {#technine-informacija}

Šis skyrius skirtas administratoriams ir sistemos priežiūrai.

### Teisės

- Prieigą kontroliuoja `PagePolicy`.
- Leidimai:
  - `pages.read.padalinys`, `pages.update.padalinys` (Padalinio puslapių redaktorius).
  - `pages.create.padalinys`, `pages.delete.padalinys` (Komunikacijos koordinatorius).
  - Globalūs `pages.*.*` (Centrinio biuro komunikacijos koordinatorius).

### Kaip tai įgyvendinta

- Modeliai: `App\Models\Page`, `App\Models\Tag`.
- Valdiklis: `App\Http\Controllers\Admin\PageController`.
- Turinio išsaugojimas ir versijavimas: `App\Services\ContentEditorService` valdo atomines turinio transakcijas, blokus, žymas ir kalbų ryšius.
- Paieškos variklis: Typesense indeksas su apribotais API raktais (`useTypesenseCollectionSource`).
- Masiniai veiksmai: `PageController::bulkUpdateStatus()` (`pages.bulkStatus`) ir `bulkDestroy()` (`pages.bulkDestroy`).
- Nuorodų peradresavimai: valdomi per `App\Models\PublicUrl` modelį.
