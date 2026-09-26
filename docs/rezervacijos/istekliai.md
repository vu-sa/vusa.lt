---
title: Ištekliai
area: resources
models: [Resource]
last_reviewed: 2026-09-26
tests:
  - tests/Feature/Admin/Reservations/ResourceControllerTest.php
  - tests/Feature/Api/Admin/ResourceAvailabilityApiControllerTest.php
  - tests/Feature/Api/Admin/ResourceApiControllerTest.php
  - tests/Unit/Models/ResourceTest.php
  - resources/js/Pages/Admin/Reservations/__tests__/ShowResource.component.test.ts
---

# Ištekliai

Išteklius yra konkretus padaliniui priklausantis daiktas ar jų rinkinys, pavyzdžiui, „Garso
kolonėlė JBL“ ×2 ar „VU SA vėliava“ ×5. Ištekliai laikomi adresu `/mano/resources`. Juos rezervuoti
gali visi platformos naudotojai, o tvarko **išteklio padalinio** valdytojai.

<DocScreenshot name="resources-index" alt="Išteklių sąrašas kortelėmis: nuotrauka, padalinys, laisvas kiekis ir mygtukas Pridėti" caption="Ištekliai kortelėmis – laisvas kiekis rodomas pasirinktam rezervacijos laikotarpiui." href="/mano/resources" />

## Kaip tai veikia

### Išteklio duomenys

| Laukas | Privalomas | Paaiškinimas |
|---|---|---|
| Pavadinimas (LT / EN) | LT – taip | Tikslus daikto pavadinimas. |
| Aprašymas (LT / EN) | LT – taip | Būklė, komplektacija, naudojimo pastabos. |
| Vieta | Taip | Kur daiktas laikomas ir kur jį atsiimti. |
| Identifikatorius | Ne | Inventorinis numeris ar kitas vidinis žymuo. |
| Padalinys | Taip | Savininkas. Rinktis galima tik iš padalinių, kuriuose esi išteklių administratorius. |
| Kiekis | Taip, ≥ 1 | Kiek vienetų iš viso turi padalinys. |
| Galima rezervuoti | Taip | Jei išjungta, išteklius matomas sąraše, bet jo įdėti į krepšelį negalima. |
| Kategorija | Ne | Žr. [Kategorijos](/rezervacijos/kategorijos). |
| Nuotraukos | Ne | JPG, PNG arba WEBP, iki 10 MB kiekviena. Jas mato ir rezervuojantys. |

### Užimtumas {#uzimtumas}

Laisvas kiekis visada skaičiuojamas **konkrečiam laikotarpiui**. Iš bendro kiekio atimami visi tuo
laikotarpiu persidengiantys rezervuoti kiekiai, kurių būsena yra **pateikta**, **rezervuota** arba
**paskolinta**. Todėl dar nepatvirtinta užklausa jau mažina laisvą kiekį, o grąžinti, atmesti ar
atšaukti daiktai jo nebemažina.

Išteklio puslapyje matyti:

- kiek vienetų laisva **dabar**;
- kas daiktą turi šiuo metu (dabartinės paskolos);
- artimiausios (iki 10) rezervacijos;
- išteklio **valdytojai** su kontaktais;
- istorija: paskutinės 50 grąžintų, atmestų ar atšauktų rezervacijų.

::: info Privatumas
Kitų žmonių rezervacijose matai tik laikotarpį ir kiekį: užimtumas viešas visiems, bet už jo
esančios rezervacijos – ne. Pilną rezervaciją mato tik tie, kas turi teisę ją peržiūrėti (žr.
[Rezervacijos → Teisės](/rezervacijos/rezervacijos#teises)).
:::

## Veiksmai

- **Sukurti išteklių** – skiltyje Ištekliai spausk „Sukurti“ ir užpildyk formą.
- **Redaguoti** – išteklio puslapyje „Redaguoti“. Sumažinus kiekį, jau pateiktos rezervacijos
  nepakeičiamos, todėl patikrink artimiausias rezervacijas.
- **Pridėti į krepšelį** – bet kuris naudotojas, jei išteklį galima rezervuoti.
- **Ištrinti / atkurti** – ištrintas išteklius patenka į sąrašo šiukšlinę ir gali
  būti atkurtas.

## Kas ką gali

| Veiksmas | Bet kuris narys | Išteklių administratorius |
|---|---|---|
| Matyti išteklius ir jų užimtumą | ✓, visų padalinių | ✓ |
| Įdėti į krepšelį ir rezervuoti | ✓ | ✓ |
| Sukurti išteklių | – | ✓, savo padaliniui |
| Redaguoti, ištrinti, atkurti | – | ✓, savo padalinio |

::: warning Redaguoti = tvarkyti rezervacijas
Išteklio redagavimo teisė ir padaro žmogų
[išteklių administratoriumi](/rezervacijos/#isteklu-administratorius): jis gauna tvirtinimo
užduotis ir sprendžia padalinio daiktų rezervacijas. Skirk rolę tik tiems, kas iš tikrųjų tvarko
daiktus.
:::


## Susitarimai

::: tip Išteklių administravimas
- Išteklių duomenis atnaujiname **du kartus per metus**: iki rugpjūčio 1 d. ir iki kovo 1 d.
- Įrašome tikslų pavadinimą, kuo išsamesnį aprašymą, nuotrauką (-as), tikslų kiekį ir pažymime, ar
  daiktą galima skolinti.
- Į sistemą keliame **visus** padalinio daiktus, nepriklausomai nuo to, ar jie skolinami.
- Pavadinimą ir aprašymą pateikiame lietuvių ir anglų kalbomis.
:::

## Techninė informacija {#technine-informacija}

### Teisės

- `ResourcePolicy`: matyti gali visi; kurti – `resources.create.padalinys` (padalinį galima
  rinktis tik iš tų, kuriems teisė galioja); redaguoti – `resources.update`, trinti –
  `resources.delete` išteklio padaliniui; visam laikui ištrinti – `resources.forceDelete`.

### Kaip tai įgyvendinta

- Laisvas kiekis skaičiuojamas pagal būsenas `created`, `reserved`, `lent`
  (`Resource::active_reservations()`).
- Šiukšlinė – tas pats sąrašas su `?showDeleted=true`.

