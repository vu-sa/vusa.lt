---
doc_status: reviewed
title: Sistemos būsena
area: systemStatus
last_reviewed: 2026-10-02
tests:
  - tests/Feature/System/SystemStatusTest.php
  - tests/Unit/Services/SystemMonitorServiceTest.php
  - resources/js/Pages/Admin/__tests__/SystemStatus.component.test.ts
---

# Sistemos būsena

Skiltis **Sistemos būsena** (`/mano/system-status`) skirta platformos techninių paslaugų ir integracijų veikimo būklei stebėti, prisijungimų statistikai peržiūrėti ir priežiūros veiksmams vykdyti.

## Kaip tai veikia

Puslapyje pateikiama trijų rūšių informacija: paslaugų sveikatos patikros, priežiūros veiksmai ir prisijungimų pagal įrenginius statistika.

### Paslaugų stebėsena

Atvėrus puslapį arba paspaudus **Atnaujinti**, pateikiamas devynių komponentų būsenos pjūvis. Automatinio periodinio atnaujinimo nėra:

- **Redis** – greitoji atmintis ir sesijų saugykla;
- **Duomenų bazė** – ryšys su MySQL duomenų baze ir lentelių būklė;
- **Talpykla** – programos duomenų talpykla;
- **Typesense** – greitosios paieškos variklis;
- **Planuoklė** – periodinių fono užduočių planuoklė;
- **Laiškų eilė** – laukiančių išsiųsti pranešimų santraukų eilė;
- **El. paštas** – SMTP pašto siuntimo integracija;
- **Sistema** – serverio PHP versija, atminties limitas ir aplinkos nustatymai;
- **Integracijos** – išorinių paslaugų (Microsoft 365, SharePoint, el. pašto ir paieškos) konfigūracija. Ši patikra nustato, ar užpildyti nustatymai, bet ryšio su paslaugomis netikrina.

Kiekviena paslauga turi būsenos ženkliuką:
- **Veikia** (žalia) – konkreti patikra sėkminga; tai nėra visų paslaugos funkcijų patvirtinimas.
- **Reikia dėmesio** (geltona) – paslauga veikia, tačiau pastebėtas vėlinimas arba išnaudotas išteklių limitas.
- **Neveikia** (raudona) – sutriko ryšys arba įvyko klaida.
- **Nežinoma** (pilka) – būsenos nepavyko nustatyti.

Kortelėje pateikiami iki keturių svarbiausių paslaugos parametrų (pvz., ryšio vėlinimas, laukiančių elementų skaičius ar versija).

### Priežiūros veiksmai

<ChangelogNote version="v3.0" date="2026-10-02" title="Priežiūros veiksmus paleisk platformoje">

Super administratorius gali paleisti šiame puslapyje pateiktus priežiūros veiksmus. Prieš patvirtindamas patikrink pasirinktą veiksmą; jo paleidimas įrašomas į veiklos žurnalą.

</ChangelogNote>

Superadministratoriui pateikiamas priežiūros veiksmų sąrašas:

- **Viešo turinio atnaujinimas** – išvalo viešųjų puslapių talpyklą;
- **Išvalyti visą talpyklą** – išvalo visos programos talpyklą (gali laikinai sulėtinti užklausas);
- **Perkrauti laiškų darbuotojus** – siunčia signalą foniniams procesams persikrauti po atnaujinimų;
- **Išsiųsti bandomąjį laišką** – patikrina el. pašto siuntimą išsiunčiant testinį laišką;
- **Sinchronizuoti viešąją paiešką** – fone atnaujina viešosios paieškos indeksus;
- **Atnaujinti institucijų aktyvumą** – fone perskaičiuoja institucijų veiklos rodiklius;
- **Sinchronizuoti Sharepoint dokumentus** – fone atnaujina susietus SharePoint failų įrašus;
- **Perindeksuoti paiešką** – fone perkuria visus paieškos indeksus (gali laikinai sutrikdyti paieškos rezultatus).

Veiksmai, pažymėti laikrodžio piktograma (**Vykdoma eilėje**), perduodami foninei užduočių eilei, todėl naršyklei nereikia laukti jų pabaigos. Veiksmai su įspėjimo piktograma (**Gali laikinai sutrikdyti naudotojų darbą**) reikalauja papildomo dėmesio.

### Prisijungimai pagal įrenginį (30 d.)

Rodo bendrą prisijungimų skaičių per pastarąjį mėnesį ir jų pasiskirstymą procentais:
- **Kompiuteriai** – prisijungimai iš stacionarių ar nešiojamųjų kompiuterių;
- **Telefonai** – prisijungimai iš išmaniųjų telefonų;
- **Planšetės** – prisijungimai iš planšetinių kompiuterių;
- **PWA paleidimai** – Mano VU SA paleidimai kaip įdiegtos mobiliosios programėlės.

Žemiau esančioje lentelėje rodomas kiekvienos dienos prisijungimų skaičius.

## Veiksmai

### Duomenų atnaujinimas

Norėdamas atnaujinti paslaugų būseną ir metrikas, puslapio viršuje paspausk **Atnaujinti**. Duomenys įkeliami iš naujo neperkraunant viso puslapio.

### Priežiūros veiksmo paleidimas

1. Rask reikiamą veiksmą skiltyje **Priežiūros veiksmai**.
2. Paspausk **Vykdyti** (su paleidimo piktograma šalia veiksmo).
3. Atvertame patvirtinimo lange perskaityk perspėjimą apie veiksmo pobūdį.
4. Paspausk **Vykdyti**.
5. Viršuje pasirodys žalias pranešimas apie sėkmingą veiksmo atlikimą arba jo perdavimą eilei.

## Kas ką gali {#teises}

| Veiksmas | Studentų atstovų koordinatorius | Narys su rolių peržiūros teise | Super Admin |
|---|---|---|---|
| Matyti sistemos būsenos puslapį | – | ✓ | ✓ |
| Matyti paslaugų būseną ir įrenginių statistiką | – | ✓ | ✓ |
| Matyti ir vykdyti priežiūros veiksmus | – | – | ✓ |

Skiltis matoma tik nariams, turintiems teisę peržiūrėti roles. Priežiūros veiksmų vykdymas yra prieinamas išskirtinai **Super Admin** rolei.

## Pranešimai ir automatizavimas {#pranesimai}

- **Foninės užduotys**: ilgiau trunkančios komandos (paieškos perindeksavimas, failų sinchronizavimas) automatiškai siunčiamos į foninę eilę, kad neužblokuotų sistemos.
- **Laiko žyma**: po paslaugų kortelėmis nurodomas tikslus paskutinio duomenų patikrinimo laikas.

## Rekomendacijos {#susitarimai}

- Priežiūros veiksmus, kurie gali laikinai sulėtinti sistemą ar išvalyti talpyklą, vykdyk tik esant būtinybei arba ne piko metu.
- Jei paslauga rodo būseną „Neveikia“, pirmiausia paspausk **Atnaujinti** ir patikrink, ar tai nebuvo trumpalaikis tinklo trukdis.

## Techninė informacija {#technine-informacija}

- Valdiklis: `SystemStatusController`, maršrutai:
  - `systemStatus` (`GET /mano/system-status`) – peržiūrai;
  - `systemStatus.maintenance` (`POST /mano/system-status/maintenance`) – priežiūros veiksmui vykdyti.
- Prieigą prie peržiūros tikrina `RolePolicy::viewAny`.
- Priežiūros užklausa: `RunSystemMaintenanceRequest`, tikrinanti `isSuperAdmin()` ir `SystemMaintenanceAction` enumą.
- Stebėjimo tarnyba: `SystemMonitorService::getAllStatus()`.
- Įrenginių apskaita: `DeviceMetricService::getRecentMetrics(30)` iš `device_metrics` lentelės.
- Galimi priežiūros veiksmai (`SystemMaintenanceAction`): `refresh-public-content`, `clear-application-cache` (trikdantis), `restart-queue-workers`, `send-test-mail`, `sync-public-search` (eilė `search:sync-public`), `refresh-institution-activity` (eilė `institutions:refresh-activity-status`), `sync-sharepoint-documents` (eilė `sharepoint:sync-documents`), `reindex-search` (eilė `search:reindex`, trikdantis).
