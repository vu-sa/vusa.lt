---
title: ViSAK
last_reviewed: 2026-09-27
tests:
  - tests/Feature/Admin/Core/CoordinatorOnRepScreensTest.php
  - tests/Feature/Responsibilities/ResponsibilityResolverTest.php
  - tests/Feature/Institutions/InstitutionScopeTest.php
  - tests/Feature/Notifications/MailTemplateRenderTest.php
  - tests/Feature/System/AdminNavigationCatalogTest.php
---

# ViSAK

**ViSAK** – studentų atstovavimo darbo sritis: posėdžiai, institucijos, darbotvarkės, problemos ir
dokumentai. Čia studentų atstovai fiksuoja, kas vyksta institucijose, kuriose jie atstovauja
studentams, o koordinatoriai seka, kaip sekasi jų padaliniui.

## Pagrindinės sąvokos {#savokos}

### Institucijos rūšis {#institucijos-rusis}

Kiekviena institucija priklauso vienai iš keturių rūšių. Rūšis nustatoma institucijos
[tipe](/sistema/tipai) (laukas **Valdysenos sritis**) ir paveldima iš aukštesnio tipo:

| Rūšis | Pavyzdžiai | Kas joje veikia |
|---|---|---|
| **VU organas** | Senatas, fakulteto taryba, studijų programos komitetas | VU SA deleguoti studentų atstovai |
| **VU SA darinys** | Parlamentas, taryba, valdybos, padaliniai, PKP | Pati VU SA |
| **Nacionalinis organas** | Nacionalinės institucijos | VU SA deleguoti atstovai |
| **Tarptautinis organas** | Tarptautinės institucijos | VU SA deleguoti atstovai |

Jei nei tipas, nei aukštesni tipai rūšies nenurodo, institucija laikoma **VU organu**. Jei
institucija turi kelis tipus ir bent vienas jų – išorinis organas, ji laikoma išorine.

### Koordinatorius {#koordinatorius}

**Koordinatorius** – žmogus, į kurį padalinio studentų atstovai kreipiasi, kai reikia pagalbos
atstovaujant. Koordinatorius priskiriamas tik **VU organams**: VU SA darinių, nacionalinių ir
tarptautinių organų atstovams koordinatorius nerodomas.

- Koordinatoriumi laikomas žmogus, einantis pareigybę su [studentų atstovų koordinavimo
  atsakomybe](/pagrindai/atsakomybes). Atsakomybė gali būti skirta konkrečiam VU organui, jo
  tipui arba visam padaliniui. Jei tokios pareigybės dabar niekas neina, koordinatorius nerodomas.
- Atstovas mato savo koordinatorių [Apžvalgoje](/visak/apzvalga#tavo-koordinatorius) ir pradžios
  puslapyje. Jei jo VU organai yra keliuose padaliniuose, Apžvalgoje rodomas kiekvieno padalinio
  koordinatorius ir kurias institucijas jis kuruoja.
- Laiškas koordinatoriui rašomas jo pareigybės el. paštu, todėl pasikeitus žmogui adresas lieka
  tas pats. Tuo pačiu adresu pasirašomi ir sistemos laiškai apie VU organus.
- Koordinatorius sau pačiam nerodomas. Apie VU SA darinių posėdžius padalinio koordinatorius
  pranešimų negauna.

::: info Koordinatorius ir sekretorius – ne tas pats
**Sekretorius** paskiriamas konkrečios institucijos kadencijai ir tvarko jos posėdžius: jam tenka
posėdžių užduotys. **Koordinatorius** padeda visiems padalinio atstovams, bet jų užduočių negauna.
:::

## Kas mato šią sritį

Sritis rodoma, jei matai bent vieną jos skiltį. Abi apžvalgos atsiveria **visiems** nariams, o
kitos skiltys – pagal rolę.

| Skiltis | Adresas | Kas mato |
|---|---|---|
| Apžvalga | `/mano/dashboard/atstovavimas` | Visi – savo institucijas |
| Padaliniai | `/mano/dashboard/atstovavimas/padaliniai` | Visi – laiko juostą; rodiklius – tik savo padalinių |
| Užduotys | `/mano/tasks/summary` | Centrinio biuro studentų atstovų koordinatorius |
| Institucijos | `/mano/institutions` | Turintys teisę matyti institucijas |
| Posėdžiai | `/mano/meetings` | Turintys teisę matyti posėdžius |
| Darbotvarkės klausimai | `/mano/agendaItems` | Turintys teisę matyti posėdžius |
| Dokumentai | `/mano/documents` | Turintys teisę matyti dokumentus |
| Problemos | `/mano/problems` | Turintys teisę matyti problemas |
| Laikotarpių tvarkyklė | `/mano/dutiables/timeline` | Komunikacijos ir studentų atstovų koordinatoriai |
| Institucijų grafas | `/mano/institutionGraph` | Turintys teisę matyti padalinio institucijas |

Mygtukas **+ Sukurti** šioje srityje siūlo: **Fiksuoti posėdį**, **Posėdžio nebuvo**, **Užbaigti
posėdį** (reikia teisės kurti posėdžius) ir **Nauja problema** (reikia teisės kurti problemas).

## Skyriaus puslapiai

- [Apžvalga](/visak/apzvalga) – tavo institucijos, jų būsenos, artimiausi posėdžiai ir koordinatorius.
- [Padaliniai](/visak/padaliniai) – padalinio rodikliai, atstovų aktyvumas ir posėdžių laiko juosta.
- [Užduotys](/visak/uzduociu-suvestine) – visų padalinio atstovų užduotys.
- [Posėdžiai](/visak/posedziai), [Darbotvarkės klausimai](/visak/darbotvarkes-klausimai),
  [Institucijos](/visak/institucijos), [Problemos](/visak/problemos), [Dokumentai](/visak/dokumentai).
