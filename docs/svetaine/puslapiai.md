---
doc_status: partial
last_reviewed: 2026-10-01
tests:
  - tests/Feature/Api/Admin/ContentEditorTest.php
  - tests/Browser/RichContentFullscreenEditorTest.php
  - tests/Feature/Admin/Content/PageControllerTest.php
title: Puslapiai
area: pages
models: [Page, Category]
---

# Puslapiai

Puslapiai – svetainės tekstai su tekstine ar vaizdine informacija, pasiekiami per nuorodas ir
meniu. Juos tvarko koordinatoriai.

Skiltis pasiekiama adresu `/mano/pages`.

::: warning Rašoma
Aprašyti turinio redagavimas, atkūrimo kopijos, kalbų versijos ir pagrindinės teisės.
Kategorijų bei puslapių struktūros instrukcijos dar pildomos.
:::

## Kategorijos

Kategorijų galimybės kol kas ribotos:

- Puslapiai, sudėti į kategoriją, rodomi kategorijos puslapyje, pvz.,
  [VU SA dokumentai](https://vusa.lt/kategorija/vu-sa-dokumentai).
- Kai kurios kategorijos veikia ypatingai. Pavyzdžiui, kategorija „Pirmakursių stovykla“ įdeda
  renginį į [Pirmakursių stovyklų](https://vusa.lt/lt/pirmakursiu-stovyklos) puslapį.

Kategorijas gali kurti tik pagrindinis administratorius.

## Turinio redagavimas {#turinio-redagavimas}

Skaitytojams redaktorius neįkeliamas. Turėdamas puslapio redagavimo teisę gali atverti turinio
redaktorių ir keisti tekstą tiesiogiai turinio bloke. Pilno ekrano režime redaguoji tik
turinio blokus; pavadinimas, viršelis ir puslapio nustatymai lieka formoje.
**Turinio struktūra** leidžia pereiti prie bloko, skyriaus ar antraštės. **Išsaugoti**
išsaugo visą formą, taip pat pakeistus aprašymą, viršelį ir svarbiausius punktus.

Puslapio formoje atverk **Redaguoti turinį**. Blokų pasirinkimo lange pasirink kategoriją arba
ieškok pagal pavadinimą. Kompiuteryje bloką įterpia jo eilutė; telefone pirmiausia pamatysi
peržiūrą, tada spausk **Pridėti šį bloką**. Peržiūros variantus gali pakeisti prieš įterpdamas.

Nuotraukų tinklelyje ir galerijoje paveikslėlį gali pakeisti paspaudęs jo peržiūrą. Kiekvienos
nuotraukos meniu gali pakeisti vietą sąraše, nustatyti fokusavimo tašką ar pašalinti nuotrauką.
Galerijos nustatymuose pasirink stulpelių skaičių, tarpus ir ar įjungti pilno dydžio peržiūrą.
Telefone nuotraukos nustatymai atsidaro tame pačiame redaktoriuje; grįžimo mygtukas parveda
prie nuotraukų.

Tvarkaraščio eilutes gali įrašyti ranka arba importuoti iš posėdžio. Importas nukopijuoja
dabartinę darbotvarkę į puslapio turinį; vėlesni posėdžio pakeitimai tvarkaraščio nekeičia.
Socialinio įrašo bloke įklijuok Facebook arba Instagram nuorodą; redaktorius parodys, ar ją
atpažino, ir pateiks peržiūrą.
Teksto laukelio bloke atsakymus gali peržiūrėti, eksportuoti arba po patvirtinimo ištrinti.

## Atkūrimo kopijos {#atkurimas}

<ChangelogNote version="v2.34" date="2026-10-01" title="Privatūs pakeitimai ir aiškesnis atkūrimas" />

Pakeitimus paskelbi arba išsaugai kaip juodraštį paspaudęs **Išsaugoti**. Kol redaguoji,
privati atkūrimo kopija saugoma šiame įrenginyje ir serveryje. Jos nemato nei lankytojai,
nei kiti redaktoriai; automatinis kopijos saugojimas nekeičia išsaugoto puslapio.

Grįžęs į redagavimą gali pamatyti **Tęsk nebaigtus pakeitimus**. Pasirink įrenginio arba
serverio kopiją ir spausk **Atkurti kopiją**. Jei kopijos skiriasi, jos rodomos atskirai.
**Atsisakyti atkūrimo kopijų** prašo patvirtinimo ir nepakeičia išsaugoto puslapio.
Neatnaujintos kopijos saugomos iki 30 dienų.

Jei neveikia ryšys, tęsk darbą šiame įrenginyje. Prisijungus serverio saugojimas bandomas
vėl. Jei naršyklė negali saugoti kopijų, formoje matai paaiškinimą – palik ją atvertą, kol
pavyks išsaugoti. Nepavykus išsaugoti pakeitimai lieka formoje.

Jei kitas redaktorius išsaugojo naujesnę puslapio versiją, tavo pakeitimai jos neperrašo.
Spausk **Peržiūrėti dabartinę versiją**, pasirink, kurią kopiją tęsti, ir išsaugok dar kartą.

## Kalbų versijos {#kalbu-versijos}

<ChangelogNote version="v2.34" date="2026-10-01" title="Abi kalbas redaguok kartu" />

Skiltyje **Kalba** pasirink tos pačios informacijos įrašą kita kalba. Paieška iš pradžių
rodo dabartinio padalinio priešingos kalbos įrašus. Pasirinktą versiją gali atverti
atskirai arba spausti **Palyginti ir redaguoti**. Kompiuteryje abi versijos rodomos greta;
telefone persijunk **LT** ir **EN**. Kiekvieną versiją išsaugai ir paskelbi atskirai.

Jei kitos versijos nėra ir gali kurti puslapius, spausk **Sukurti versiją kita kalba**.
Nauja versija atsidaro tuščia ir iš pradžių yra juodraštis; padalinį, žymas, viršelį ir
rodymo nustatymus ji perima iš originalo. Jei patogiau versti ant esamo teksto, spausk
**Nukopijuoti LT turinį** (arba EN) – įkeliamas dabartinis pavadinimas, tekstas ir blokai,
įskaitant dar neišsaugotus pakeitimus. Jei originalas dar nesukurtas, pirmiausia išsaugok
jį, tada kitą versiją.

Susiejimas išsaugomas kartu su forma. Jei pasirinkta versija jau susieta su kitu įrašu,
prieš pakeisdamas ryšį matai paveikiamus įrašus ir turi patvirtinti pakeitimą. Turi galėti
redaguoti visus paveikiamus įrašus. Ryšį pašalini pasirinkimo lange spausdamas **Atsieti kalbų
versijas** – jis panaikinamas išsaugojus.

### Automatiškai pildomi blokai {#automatiniai-blokai}

Naujienų, renginių, nuorodų ir institucijų sąrašų blokai pasipildo patys. Paskelbta naujiena,
renginys ar pakeistas puslapis juose matomi iš karto. Praėjęs renginys iš artėjančių sąrašo
dingsta per 10 minučių.

## Kas ką gali {#teises}

Padalinio svetainės tekstus atnaujina rolę **Padalinio puslapių redaktorius** turinčios pareigybės:
jos gali redaguoti esamus padalinio puslapius, bet ne kurti ar trinti. Naujus puslapius kuria ir
struktūrą keičia komunikacijos koordinatoriai.

## Techninė informacija {#technine-informacija}

### Teisės

- Padalinio puslapių redaktorius: `pages.read.padalinys`, `pages.update.padalinys`.
