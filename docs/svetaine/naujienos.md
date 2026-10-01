---
doc_status: partial
last_reviewed: 2026-10-01
tests:
  - tests/Feature/Api/Admin/ContentEditorTest.php
  - tests/Feature/Admin/Content/NewsControllerTest.php
  - tests/Browser/RichContentFullscreenEditorTest.php
title: Naujienos
area: news
models: [News]
---

# Naujienos

Naujienos veikia panašiai kaip puslapiai, bet turi **skelbimo laiką** ir rodomos naujienų
skiltyje. Kiekvienas padalinys skelbia savo naujienas savo svetainės dalyje. Naują naujieną galima
sukurti per **+ Sukurti → Nauja naujiena**.

Skiltis pasiekiama adresu `/mano/news`.

## Turinio redagavimas {#turinio-redagavimas}

Naujienos turinio blokai redaguojami taip pat kaip [puslapiuose](/svetaine/puslapiai#turinio-redagavimas):
telefone prieš įterpdamas bloką matai jo peržiūrą, o nuotraukų ir tvarkaraščio papildomi
nustatymai atsidaro tame pačiame redaktoriuje.

## Išsaugojimas ir paskelbimas {#issaugojimas}

<ChangelogNote version="v2.34" date="2026-10-01" title="Patikimesnis išsaugojimas" />

**Juodraštis** matomas tik sistemoje. Pasirinkęs **Paskelbta** ir išsaugojęs, naujieną
padarai pasiekiamą pagal viešą nuorodą. Jei paskelbimo laikas ateityje, paieškoje ir
naujienų sąrašuose ji pasirodys nuo pasirinkto laiko; pati nuoroda jau veikia.

Įvadiniame tekste gali būti iki 200 matomų simbolių. Formatavimo žymos į šį skaičių
neįtraukiamos. Senesnį ilgesnį įvadą gali palikti nepakeistą; pakeistas įvadas turi tilpti
į ribą. Pilno ekrano **Išsaugoti** išsaugo ir likusius naujienos laukus.

## Atkūrimo kopijos ir kalbos {#atkurimas}

Redaguojant saugomos privačios įrenginio bei serverio atkūrimo kopijos. Jos nepaskelbia
naujienos. Grįžęs į formą pasirink **Atkurti kopiją**; jei išsaugota versija pasikeitė,
**Peržiūrėti dabartinę versiją** leidžia pasirinkti, kurią kopiją tęsti.
[Atkūrimo eiga](/svetaine/puslapiai#atkurimas) vienoda puslapiams ir naujienoms.

**Palyginti ir redaguoti** atveria abi kalbas greta, o telefone leidžia persijungti.
Kiekviena versija turi savo įvadą, svarbiausius punktus, paskelbimo laiką ir išsaugojimą.
**Sukurti versiją kita kalba** atveria tuščią juodraštį, į kurį gali nukopijuoti dabartinį
turinį; išsaugojus versijos susiejamos. [Kalbų versijų eiga](/svetaine/puslapiai#kalbu-versijos).

## Kas ką gali {#teises}

Rolę **Komunikacijos koordinatorius** turinčios pareigybės gali kurti, redaguoti,
paskelbti ir trinti savo padalinio naujienas. Susiedamas kalbų versijas turi galėti
redaguoti abu įrašus ir kitus įrašus, kurių ryšys būtų pakeistas.

::: warning Rašoma
Aprašyti turinio redagavimas, išsaugojimas, atkūrimas, kalbos ir pagrindinės teisės.
Naujienų sąrašo bei kitų valdymo veiksmų instrukcijos dar pildomos.
:::

## Techninė informacija {#technine-informacija}

Išsaugojimas tikrina įrašo versiją ir vienu veiksmu atnaujina naujieną, turinio blokus,
žymas bei kalbų ryšius. Atkūrimo kopijos saugomos atskirai nuo paskelbtų naujienų.
