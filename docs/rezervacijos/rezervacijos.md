---
title: Rezervacijos
area: reservations
models: [Reservation, ReservationResource, Approval]
last_reviewed: 2026-09-26
tests:
  - tests/Feature/Admin/Reservations/ReservationControllerTest.php
  - tests/Feature/Admin/Reservations/ReservationCartTest.php
  - tests/Feature/Admin/Reservations/ResourceReservationTest.php
  - tests/Feature/Admin/Dashboard/ReservationsDashboardTest.php
  - tests/Feature/Approvals/ApprovalServiceTest.php
  - tests/Feature/Approvals/ApprovalControllerTest.php
  - tests/Feature/Tasks/Subscribers/ReservationTaskSubscriberTest.php
  - resources/js/Components/Reservations/__tests__/ReservationCart.component.test.ts
  - resources/js/Components/Reservations/__tests__/ReservationDecisionDialog.component.test.ts
  - resources/js/Components/Reservations/__tests__/ReservationResourceList.component.test.ts
  - resources/js/Pages/Admin/Reservations/__tests__/ShowReservation.component.test.ts
  - resources/js/Utils/__tests__/ReservationStatus.test.ts
---

# Rezervacijos

Rezervacija yra užklausa pasiskolinti vieną ar kelis išteklius tam tikram laikotarpiui. Ją sudaro:

- pavadinimas;
- aprašymas;
- laikotarpis (nuo atsiėmimo iki grąžinimo);
- teikėjai;
- rezervuojamų išteklių sąrašas su kiekiais.

Rezervacijų sąrašas pasiekiamas adresu `/mano/reservations`, o kiekviena rezervacija turi savo
puslapį `/mano/reservations/{id}`.

## Kaip tai veikia

### Krepšelis

Rezervacija pradedama nuo **krepšelio**. Skiltyje **Rezervacijos → Ištekliai** prie išteklio
spausk „Pridėti“, ir jis patenka į tavo krepšelį. Krepšelis:

- priklauso tik tau. Adrese nėra jo numerio, todėl kito žmogaus krepšelio pasiekti neįmanoma;
- **išsaugomas serveryje**, todėl jį matai ir kitame įrenginyje;
- ištrinamas automatiškai, jei jo nekeitei **14 dienų**;
- **nerezervuoja daiktų**. Kol renkiesi, tuos pačius daiktus gali užsirezervuoti kiti.

<ChangelogNote version="v2.21" date="2026-09-24" title="Rezervacija išsaugoma kaip juodraštis">

Krepšelis atsirado v2.21: iki tol rezervacijos forma neišsaugodavo pasirinktų išteklių, o
pakeitus laiką jie išsivalydavo. Kiekis dabar tikrinamas ir pateikiant, net jei du žmonės
pateikia vienu metu.

</ChangelogNote>

Jei kas nors pateikia rezervaciją, dėl kurios tavo krepšelyje pasirinkto kiekio tuo laikotarpiu
nebeužtenka, gauni pranešimą. Krepšelyje tada matysi, kiek dar laisva, ir galėsi sumažinti kiekį
arba pakeisti laiką.

### Pateikimas

<DocScreenshot name="reservation-form" alt="Rezervacijos pateikimo forma su pavadinimu, laikotarpiu ir krepšelio ištekliais" caption="Pateikimas: krepšelio ištekliai, laikotarpis ir rezervacijos aprašymas." href="/mano/reservations/create" />

Pateikiant krepšelį (`/mano/reservations/create` → „Pateikti“) sistema:

1. **Dar kartą patikrina užimtumą**. Patikrinimas daromas užrakinus eilutes, todėl dvi tuo pačiu
   metu pateiktos rezervacijos negali paimti to paties paskutinio daikto. Jei kiekio nebeužtenka,
   rezervacija nesukuriama, o krepšelis lieka nepakeistas.
2. Sukuria rezervaciją ir kiekvienam ištekliui priskiria būseną **pateikta**. Visi ištekliai gauna
   rezervacijos laikotarpį.
3. Tave pažymi rezervacijos teikėju ir ištrina tavo krepšelį.
4. Kiekvienam ištekliui paprašo patvirtinimo: jo padalinio valdytojai gauna pranešimą ir užduotį.

::: tip Viena rezervacija – daug padalinių
Toje pačioje rezervacijoje gali būti išteklių iš kelių padalinių. Kiekvieną išteklių tvirtina
**jo** padalinio valdytojai, nepriklausomai nuo kitų, todėl rezervacijos būsena gali būti
„Mišri“.
:::

### Užimtumas

Išteklio kiekis laikomas užimtu, kol jo rezervacija yra būsenos **pateikta**, **rezervuota** arba
**paskolinta**. Tai reiškia, kad dar nepatvirtinta užklausa jau „laiko“ daiktus. Rodomas laisvas
kiekis visada skaičiuojamas pasirinktam laikotarpiui. Plačiau – [Ištekliai](/rezervacijos/istekliai#uzimtumas).

### Rezervacijos būsenos {#busenos}

Būseną turi kiekvienas rezervacijos išteklius. Rezervacijos būsena sąraše yra jos išteklių būsenų
suvestinė.

| Būsena | Reikšmė | Kas toliau |
|---|---|---|
| **pateikta** | Užklausa pateikta, laukiama valdytojo sprendimo. | Valdytojas **tvirtina** → rezervuota, arba **atmeta** → atmesta. Teikėjas gali **atšaukti**. |
| **rezervuota** | Valdytojas patvirtino, daiktas laukia atsiėmimo. | Valdytojas **išduoda** → paskolinta. Teikėjas gali **atšaukti**. |
| **paskolinta** | Daiktas atiduotas teikėjui. | Valdytojas pažymi **grąžinta**. |
| **grąžinta** | Daiktas grąžintas, užfiksuotas grąžinimo laikas. | Galutinė būsena. |
| **atmesta** | Valdytojas atmetė užklausą. Priežastis – pastabose arba komentaruose. | Galutinė būsena. |
| **atšaukta** | Teikėjas atšaukė užklausą iki atsiėmimo. | Galutinė būsena. |

Sistema leidžia tik šiuos perėjimus:

```text
pateikta ──tvirtinti──▶ rezervuota ──išduoti──▶ paskolinta ──grąžinti──▶ grąžinta
    │                       │
    ├──atmesti──▶ atmesta    └──atšaukti──▶ atšaukta
    └──atšaukti─▶ atšaukta
```

Veiksmas, kurio dabartinė būsena neleidžia (pavyzdžiui, atmesti jau išduotą daiktą), atmetamas dar
prieš jį išsaugant.

## Veiksmai

### Valdytojo sprendimai

<DocScreenshot name="reservation-decisions" alt="Rezervacijos puslapis: kiekvienas išteklius su savo būsena ir kitu veiksmu – Grąžinti, Išduoti, Tvirtinti" caption="Kiekvienas rezervacijos išteklius turi savo būseną ir savo kitą veiksmą." />

Rezervacijos puslapyje ir apžvalgoje prie kiekvieno išteklio valdytojas mato vieną mygtuką – kitą
leistiną veiksmą: **Tvirtinti** (pateikta), **Išduoti** (rezervuota) arba **Grąžinti** (paskolinta).
**Atmesti**, atšaukti, redaguoti ir pašalinti galima per meniu **⋯** šalia. Veiksmus galima atlikti
ir su keliais pasirinktais ištekliais iš karto.

- **Dalinis patvirtinimas.** Tvirtindamas gali nurodyti mažesnį kiekį nei prašyta, bet ne mažesnį
  nei 1 ir ne didesnį nei prašyta. Rezervacijos kiekis sumažinamas iki patvirtinto.
- **Pastabos.** Prie kiekvieno sprendimo galima parašyti pastabą. Atmetant ji tampa priežastimi,
  kurią mato teikėjas.
- **Užbaigti.** Iš karto perkelia išteklius į būseną „grąžinta“, praleisdamas likusius žingsnius.
  Kiekvienas praleistas žingsnis vis tiek įrašomas į istoriją.
- **Atšaukti paskutinį veiksmą.** Jei suklydai, išteklius grąžinamas viena būsena atgal:
  rezervuota → pateikta, paskolinta → rezervuota, grąžinta → paskolinta. Atšauktas patvirtinimas lieka
  istorijoje su tavo nurodyta priežastimi.

### Teikėjo veiksmai

- **Atšaukti** išteklių ar visą rezervaciją, kol daiktas neišduotas (būsena „pateikta“ arba
  „rezervuota“).
- **Pridėti išteklių** prie jau sukurtos rezervacijos, pakeisti jo kiekį arba laiką. Norėdamas vienam
  daiktui nustatyti kitą skolinimosi laiką nei visai rezervacijai, pridėk jį jau sukūręs rezervaciją.
- **Pridėti kitus teikėjus.** Pridėti naudotojai gauna pranešimą ir tampa lygiaverčiais teikėjais:
  mato rezervaciją, gali ją keisti ir atšaukti.
- **Ištrinti** rezervaciją. Ji perkeliama į šiukšlinę ir gali būti atkurta.

Pačios rezervacijos (pavadinimo, laikotarpio) po pateikimo tiesiogiai redaguoti negalima. Keičiami
tik jos ištekliai.

## Kas ką gali {#teises}

Čia svarbios dvi rolės: rezervacijos **teikėjas** ir
[išteklių administratorius](/rezervacijos/#isteklu-administratorius).

| Veiksmas | Bet kuris narys | Teikėjas | Išteklių administratorius |
|---|---|---|---|
| Sukurti rezervaciją | ✓ | ✓ | ✓ |
| Matyti rezervaciją | – | ✓ | ✓, jei joje yra jo padalinio daiktų |
| Pridėti ar keisti išteklius, pridėti teikėjų | – | ✓ | ✓ |
| Atšaukti išteklių (kol neišduotas) | – | ✓ | ✓ |
| Tvirtinti, atmesti, išduoti, grąžinti, užbaigti | – | – | ✓, tik savo padalinio daiktus |
| Atšaukti paskutinį veiksmą | – | – | ✓, tik savo padalinio daiktus |
| Ištrinti rezervaciją | – | ✓ | ✓ |

::: warning Tik savo padalinio daiktai
Tvirtinimo teisė tikrinama pagal **daikto** padalinį. MIF administratorius mato rezervaciją, kurioje
yra MIF daiktas, bet tos pačios rezervacijos FSF daikto tvirtinti negali. Sąraše jis pažymėtas
„Šis išteklius priklauso padaliniui … – jo tvirtinti negali“.
:::

::: info Niekas negauna pranešimų?
Pranešimus ir užduotis gauna visi, kas **šiuo metu** eina padalinio pareigybę su išteklių
administratoriaus role. Jei niekas jų negauna, patikrink, ar pareigybė turi rolę ir ar pareigas
einantis žmogus pridėtas prie pareigybės. **Centrinio biuro išteklių administratorius** gauna
pranešimus ir užduotis apie CB daiktus, o kitų padalinių užklausas mato apžvalgoje (žr.
[Centrinio biuro išteklių administratorius](/rezervacijos/#centrinio-biuro)).
:::


## Pranešimai ir automatika

| Kada | Kas gauna | Ką |
|---|---|---|
| Pateikiama rezervacija arba pridedamas išteklius | Išteklio valdytojai | Pranešimą „laukia patvirtinimo“ ir **tvirtinimo užduotį** |
| Pasikeičia išteklio būsena | Visi teikėjai, išskyrus pakeitusįjį | Pranešimą su nauja būsena |
| Išteklius tampa „rezervuota“ | Teikėjai | Užduotį **„Atsiimti rezervacijos išteklius“**, kurios terminas yra vėliausias atsiėmimo laikas |
| Išteklius tampa „paskolinta“ | Teikėjai | Užduotį **„Grąžinti rezervacijos išteklius“**, kurios terminas yra vėliausias grąžinimo laikas |
| Visi ištekliai atsiimti / grąžinti | Teikėjai | Užduotis pažymima atlikta automatiškai |
| Kito pateikta rezervacija užima tavo krepšelio daiktus | Krepšelio savininkas | Pranešimą, kurių daiktų nebeužtenka |
| Pridedamas naujas teikėjas | Pridėtas naudotojas | Pranešimą apie priskyrimą |

Užduočių eiga (pvz., „2 iš 3“) skaičiuojama pagal išteklių būsenas. Rankiniu būdu jų užbaigti
nereikia. Plačiau apie užduotis – [Užduotys](/mano/uzduotys).

## Susitarimai {#susitarimai}

Šie susitarimai yra organizaciniai: sistema jų neužtikrina, bet valdytojai jų laikosi.

::: tip Rezervacijos atlikimas
- Daiktus rezervuok likus **bent 7 darbo dienoms** iki renginio ar mokymų.
- Pavadinime įrašyk tikslų renginio ar mokymų pavadinimą.
- Aprašyme nurodyk, kam skirti daiktai, kada planuoji juos atsiimti ir kas atsiims.
- Laikotarpis – nuo atsiėmimo iki grąžinimo.
- Daiktus iš kelių padalinių (VU SA CB, VU SA P, studentų iniciatyvų) rinkis į vieną rezervaciją.
  Atskirų kurti nereikia.
- Pateikdamas rezervaciją patvirtini, kad visiškai atsakai už daiktų grąžinimą sutartu laiku, jų
  būklę ir žalos atlyginimą, jei ji padaryta. Perdavimo akto pasirašyti nebereikia, nes jis
  skaitmenizuotas.
:::

::: tip Rezervacijos administravimas
- Valdytojas užklausą patvirtina arba atmeta per **1–2 darbo dienas**.
- Komentaruose valdytojas nurodo, kada daiktas bus paruoštas atsiimti, ir daiktą perduosiančio
  žmogaus kontaktus.
- Atmesdamas valdytojas visada nurodo priežastį.
:::

::: details Dažni klausimai
**Ar yra limitas, kiek daiktų galima skolintis?** Ne. Galima skolintis tiek, kiek įkelta į sistemą
ir laisva pasirinktu laikotarpiu.

**Patvirtinau išteklį, bet negaliu jo paskolinti. Ką daryti?** Naudok **Atšaukti paskutinį
veiksmą**: išteklius grįš į būseną „pateikta“, tada jį atmesk ir komentare nurodyk priežastį.

**Daiktas sugadintas prieš renginį, per jį ar po jo.** Pirmiausia informuok daikto padalinio
išteklių administratorių (jei jo nėra – padalinio ar iniciatyvos vadovą) ir sutarkite, ar reikės
atlyginti nuostolius.
:::

## Techninė informacija {#technine-informacija}

Šis skyrius skirtas tiems, kurie tvarko roles ar tikrina sistemos elgseną.

### Teisės

- Rezervacijos teises sprendžia `ReservationPolicy`. Rezervacijos išteklių (tarpinės eilutės)
  teisės visada perduodamos rezervacijai (`ReservationResourcePolicy`).
- Išteklių administratorius = `resources.update.padalinys` išteklio padalinyje
  (`permission.resource_managership_indicating_permission`). `resources.update.*` (Centrinio
  biuro rolė) apima visus padalinius.
- Išteklių administratoriaus rolė: `resources.create|update|delete.padalinys`, `resources.read.*`,
  `reservations.create|read|update|delete.padalinys`.
- Be `reservations.read.padalinys` rezervacijų sąraše rodomos tik tavo rezervacijos, nepaisant
  filtrų. Visam laikui ištrinti gali tik turintys `reservations.forceDelete`.

### Kaip tai įgyvendinta

- Būsenų perėjimai apibrėžti `ReservationResourceState::config()`; neleistinas sprendimas
  atmetamas `ReservationResource::isDecisionAllowed()`.
- Pateikiant kiekis tikrinamas užrakinus eilutes (`EnsureReservationCapacity`).
- Krepšelio galiojimas – `config('vusa.reservation_draft_ttl_days')` (14 d.).
- Užduotis ir pranešimus apie naujas užklausas gauna `GetResourceManagers` grąžinami žmonės:
  dabartiniai išteklio padalinio pareigybių nariai, kurių rolė turi `resources.update.padalinys`
  arba `resources.update.*`.

