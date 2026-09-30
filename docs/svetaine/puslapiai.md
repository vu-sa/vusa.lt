---
doc_status: draft
title: Puslapiai
area: pages
models: [Page, Category]
---

# Puslapiai

Puslapiai – svetainės tekstai su tekstine ar vaizdine informacija, pasiekiami per nuorodas ir
meniu. Juos tvarko koordinatoriai.

Skiltis pasiekiama adresu `/mano/pages`.

::: warning Rašoma
Šis puslapis dar rašomas. Kai bus baigtas, jame bus skyriai **Kaip tai veikia**, **Veiksmai**,
**Kas ką gali** ir **Pranešimai ir automatizavimas**, kaip [Rezervacijų](/rezervacijos/rezervacijos) skyriuje.
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
redaktorių ir toliau keisti tekstą tiesiogiai peržiūroje.

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
