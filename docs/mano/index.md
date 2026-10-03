---
doc_status: reviewed
title: Mano
area: dashboard
last_reviewed: 2026-09-27
tests:
  - resources/js/Pages/Admin/__tests__/ShowAdminHome.component.test.ts
  - tests/Browser/AdminHomeLayoutTest.php
  - tests/Feature/Admin/Core/DashboardControllerTest.php
  - resources/js/Components/Layouts/Shell/__tests__/ShellAccountMenu.component.test.ts
---

# Mano

Darbo sritis **Mano** – pirmas puslapis po prisijungimo (`/mano`). Ji atsako į klausimą *kas laukia
manęs?*: tau priskirtos užduotys, artimiausi tavo posėdžiai, tavo rezervacijos ir nauji pranešimai.
Sritis rodoma **visiems** naudotojams.

| Skiltis | Adresas | Kas mato |
|---|---|---|
| Apžvalga | `/mano` | Visi |
| Užduotys | `/mano/tasks` | Visi – tik savo užduotis |
| Pranešimai | `/mano/notifications` | Visi – tik savo pranešimus |

<DocScreenshot name="admin-home" alt="Mano VU SA pradžia: tavo užduotys, greiti veiksmai ir artimiausi posėdžiai" caption="Pradžia – užduotys ir artimiausi tavo posėdžiai vienoje vietoje." href="/mano" />

<DocScreenshot name="admin-home-phone" narrow alt="Pradžia telefone: užduotys, institucijos ir apatinė juosta su Mano, Užduotys, + Sukurti, Pranešimai ir Meniu" caption="Telefone skiltys ir + Sukurti perkeliami į apatinę juostą." />

Pradžios puslapio turinys priklauso nuo tavo pareigybių: studentų atstovas mato savo institucijų
posėdžius ir jų užduotis, išteklių valdytojas – tvirtinimo laukiančias rezervacijas.

## Pradėti ir grįžti prie darbo {#pradzia}

<ChangelogNote version="v3.0" date="2026-10-02" title="Pradžia rodo tai, ką turi padaryti">

Užduotys ir artimiausi posėdžiai padeda grįžti prie darbo. Nebaigtą rezervaciją tęsk iš jos
nuorodos; senasis pirmųjų žingsnių kontrolinis sąrašas ir pareigų pokyčių juosta pašalinti.

</ChangelogNote>

**Eiti į** rodo tau prieinamas sritis ir skiltis. **+ Sukurti** atveria leidžiamus kūrimo
veiksmus. ViSAK apžvalga skirta tavo institucijoms, [Padaliniai](/visak/padaliniai) – laiko
juostai ir prieinamiems padalinių rodikliams.

Jei pradėjai rezervaciją, bet jos nepateikei, Pradžioje gali ją tęsti. Krepšelis saugomas
serveryje ir daiktų dar neužima (plačiau – [Rezervacijos](/rezervacijos/rezervacijos)).

Pirmą kartą Pradžios turas parodo svarbiausias vietas. Jį vėl paleisi per paskyros meniu
**Pagalba → Parodyk, kaip veikia**, kai puslapis turi turą. Savo pareigas ir prieigos pokyčių
istoriją rasi [Paskyra ir prieiga](/mano/paskyra-ir-prieiga), o gautus pranešimus –
[Pranešimų puslapyje](/mano/pranesimai).

## Techninė informacija {#technine-informacija}

- Pradžios puslapis – `ShowAdminHome.vue`; skyriai, kurių tau nereikia (pvz., institucijos, kai jų
  neturi), nerodomi, o likę užima jų vietą.
- Kad puslapis telpa telefone, planšetėje ir kompiuteryje (390, 820, 1180 ir 1440 px pločio ekranuose,
  šviesia ir tamsia tema), tikrina `AdminHomeLayoutTest`.
