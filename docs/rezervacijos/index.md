---
title: Rezervacijos
coverage: ignore
---

# Rezervacijos

Darbo sritis **Rezervacijos** skirta VU SA įrangai ir daiktams skolinti: garso technikai, atributikai,
renginių inventoriui. Kiekvienas padalinys, taip pat VU SA CB ir studentų iniciatyvos, sistemoje
laiko savo **išteklius**. Kiti gali juos rezervuoti vienoje vietoje, nekurdami atskirų užklausų
kiekvienam padaliniui.

Sritį sudaro trys dalys:

- **Rezervacija** – kas, kam ir kuriam laikui skolinasi.
- **Išteklius** – konkretus daiktas su kiekiu ir padaliniu savininku.
- **Kategorija** – išteklių grupavimas.

Kiekvienas rezervacijoje pasirinktas išteklius turi **savo būseną**, todėl vienos rezervacijos
daiktus gali tvirtinti skirtingų padalinių valdytojai.

## Kas mato šią sritį

Rezervuoti gali **kiekvienas** prisijungęs narys, todėl sritis rodoma visiems. Ką joje gali daryti,
lemia tai, ar esi išteklių administratorius.

| Skiltis | Adresas | Narys | Išteklių administratorius |
|---|---|---|---|
| Apžvalga | `/mano/dashboard/reservations` | Savo rezervacijos | Taip pat laukiančios sprendimo |
| Rezervacijos | `/mano/reservations` | Tik savo rezervacijos | Visos jo padalinio išteklių rezervacijos |
| Ištekliai | `/mano/resources` | Visų padalinių ištekliai, gali rezervuoti | Taip pat kuria ir redaguoja savo padalinio |
| Kategorijos | `/mano/resourceCategories` | – | Kuria ir redaguoja |

Mygtukas **+ Sukurti** šioje srityje siūlo veiksmą **Nauja rezervacija**.

## Išteklių administratorius {#isteklu-administratorius}

Visa sritis sukasi apie vieną rolę – **Išteklių administratorius**. Ją paprastai turi padalinio
**pirmininko** ir **administratoriaus** pareigybės. Rolė galioja tik **savo padalinyje**: MIF
administratorius tvarko MIF daiktus, bet ne FSF.

Išteklių administratorius:

- **kuria ir redaguoja** savo padalinio išteklius ir kategorijas;
- **gauna užduotį ir pranešimą**, kai kas nors rezervuoja jo padalinio daiktą;
- **tvirtina, atmeta, išduoda** ir pažymi **grąžintus** savo padalinio daiktus;
- **mato** visas rezervacijas, kuriose yra jo padalinio daiktų, net jei jas sukūrė kitų padalinių nariai;
- kaip ir visi, gali pats **rezervuoti** bet kurio padalinio daiktus.

### Centrinio biuro išteklių administratorius {#centrinio-biuro}

Centrinio biuro išteklių administratorius (VU SA CB) gali tas pačias teises naudoti **visuose**
padaliniuose, bet pranešimai jį pasiekia tik apie paties CB daiktus. Taip jis gali padėti bet
kuriam padaliniui, bet nėra užverčiamas visos organizacijos užklausomis.

| | Išteklių administratorius | Centrinio biuro išteklių administratorius |
|---|---|---|
| Kuria ir redaguoja išteklius | Savo padalinio | Visų padalinių |
| Tvirtina, išduoda, pažymi grąžintus | Savo padalinio daiktus | Bet kurio padalinio daiktus |
| Gauna užduotį ir pranešimą apie naują užklausą | Apie savo padalinio daiktus | Apie CB daiktus |
| Mato apžvalgoje „Laukia tavo sprendimo“ | Savo padalinio daiktus | Visų padalinių daiktus |

::: tip Kada kreiptis į CB
Jei padalinio administratorius ilgai nesprendžia užklausos (pvz., atostogauja), CB išteklių
administratorius gali ją patvirtinti ar atmesti vietoje jo.
:::

::: tip Kaip paskirti išteklių administratorių
Rolė skiriama **pareigybei**, ne žmogui: **Organizacija → Pareigybės →** pareigybė **→ Redaguoti →
Rolės**. Taip naujas administratorius ją gauna automatiškai, vos pradėjęs eiti pareigas, o buvęs
ją praranda. Savo roles kiekvienas mato puslapyje „Mano rolės ir pareigybės“.
:::

::: info Rezervacijos teikėjas
Antra šiame skyriuje minima rolė nėra sistemos rolė: **teikėjas** yra rezervaciją sukūręs arba
prie jos pridėtas narys. Jis gali keisti ir atšaukti savo rezervaciją.
:::

## Apžvalga

<DocScreenshot name="reservations-overview" alt="Rezervacijų apžvalga: skaičiai, tavo rezervacijos ir laukiančios tavo sprendimo" caption="Išteklių administratoriaus apžvalga – laukiančias užklausas galima patvirtinti iš karto." href="/mano/dashboard/reservations" />

Apžvalgos puslapyje matyti:

- ištekliai, **laukiantys tavo sprendimo** (tik valdytojams);
- kiek daiktų šiuo metu išduota;
- kiek daiktų vėluoja grąžinti;
- tavo aktyvios ir vėluojančios rezervacijos.

Vėluojanti rezervacija yra ta, kurios numatytas pabaigos laikas praėjo, o bent vienas išteklius vis
dar yra būsenos „pateikta“, „rezervuota“ arba „paskolinta“.

## Skyriaus puslapiai

- [Rezervacijos](/rezervacijos/rezervacijos) – krepšelis, pateikimas, būsenos, tvirtinimas, užduotys ir pranešimai.
- [Ištekliai](/rezervacijos/istekliai) – išteklių kūrimas, kiekis ir užimtumas.
- [Kategorijos](/rezervacijos/kategorijos) – išteklių grupavimas.
