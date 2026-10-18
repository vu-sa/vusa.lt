---
doc_status: reviewed
title: Mano
area: dashboard
last_reviewed: 2026-10-17
tests:
  - resources/js/Pages/Admin/__tests__/ShowAdminHome.component.test.ts
  - resources/js/Pages/Admin/__tests__/ShowOnboarding.component.test.ts
  - tests/Feature/Admin/Core/OnboardingControllerTest.php
  - tests/Feature/Api/Admin/OnboardingApiTest.php
  - tests/Browser/OnboardingChecklistTest.php
  - resources/js/Components/Home/__tests__/SiteContentLists.component.test.ts
  - tests/Browser/AdminPublicLinksTest.php
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
| Pirmieji žingsniai | `/mano/onboarding` | Visi – tik savo pažangą |
| Užduotys | `/mano/tasks` | Visi – tik savo užduotis |
| Pranešimai | `/mano/notifications` | Visi – tik savo pranešimus |

<DocScreenshot name="admin-home" alt="Mano VU SA pradžia: tavo užduotys, greiti veiksmai ir artimiausi posėdžiai" caption="Pradžia – užduotys ir artimiausi tavo posėdžiai vienoje vietoje." href="/mano" />

<DocScreenshot name="admin-home-phone" phone alt="Pradžia telefone: užduotys, institucijos ir apatinė juosta su Mano, Užduotys, Veiksmai, Pranešimai ir Meniu" caption="Telefone skiltys ir Veiksmai perkeliami į apatinę juostą." />

Pradžios puslapio turinys priklauso nuo tavo pareigybių: studentų atstovas mato savo institucijų
posėdžius ir jų užduotis, išteklių valdytojas – tvirtinimo laukiančias rezervacijas.

**Naujausios naujienos** ir **Artimiausi renginiai** veda į viešą svetainę. Paspaudus įrašą,
jo viešas puslapis atveriamas tame pačiame naršyklės skirtuke; atgal galima grįžti naršyklės mygtuku.

## Pradėti ir grįžti prie darbo {#pradzia}

<ChangelogNote version="v3.0" date="2026-10-02" title="Pradžia rodo tai, ką turi padaryti">

Užduotys ir artimiausi posėdžiai padeda grįžti prie darbo. Nebaigtą rezervaciją galima tęsti iš jos
nuorodos, o pareigų pokyčių istorija pateikiama atskirai nuo darbų.

</ChangelogNote>

**Eiti į** rodo tau prieinamas sritis ir skiltis. **Veiksmai** atveria leidžiamus
veiksmus. **Greiti veiksmai** kompiuteryje rodo iki keturių dažniausiai laukiamų veiksmų: pirmiausia
**Fiksuoti posėdį** ir šalia jo **Posėdžio nebuvo**, tada **Nauja problema** ir po vieną kitų tau
prieinamų sričių veiksmą. ViSAK apžvalga skirta tavo institucijoms, [Padaliniai](/visak/padaliniai) – laiko
juostai ir prieinamiems padalinių rodikliams.

Jei pradėjai rezervaciją, bet jos nepateikei, Pradžioje gali ją tęsti. Krepšelis saugomas
serveryje ir daiktų dar neužima (plačiau – [Rezervacijos](/rezervacijos/rezervacijos)).

Pradžios turas paleidžiamas pasirinktinai per **Pirmieji žingsniai** arba paskyros meniu
**Pagalba → Parodyk, kaip veikia**, jei puslapis turi turą. Pareigos ir prieigos pokyčių
istorija pateikiama puslapyje [Paskyra ir prieiga](/mano/paskyra-ir-prieiga), o gauti pranešimai –
[Pranešimų puslapyje](/mano/pranesimai).

## Pirmieji žingsniai {#pirmieji-zingsniai}

<ChangelogNote version="v3.2" date="2026-10-17" title="Pasirenkamas paskyros paruošimo sąrašas">

Pradžioje po užduotimis rodoma trumpa pažangos suvestinė. Visas sąrašas pasiekiamas skiltyje
**Pirmieji žingsniai** – ir naujiems, ir anksčiau prisijungusiems nariams.

</ChangelogNote>

Atverk [Pirmieji žingsniai](app:/mano/onboarding). Šeši žingsniai padeda patikrinti pareigas ir
kontaktus, pasirinkti pranešimų nustatymus, įsidiegti programą, įjungti naršyklės pranešimus ir
sužinoti, kur rasti savo darbus bei pagalbą. Juos gali atlikti bet kuria tvarka, grįžti vėliau
arba vėl atverti pažymėtą žingsnį. Vien atverti nustatymų nuorodą nepakanka – peržiūrą patvirtini pats.

**Slėpti pradžioje** pašalina tik suvestinę. Sąrašas ir pažanga lieka, o suvestinę gali grąžinti
paspaudęs **Rodyti pradžioje**. Peržiūrėjus visus žingsnius suvestinė paslepiama automatiškai.
Įdiegimą ir naršyklės pranešimus galima praleisti; pažangoje tai įskaitoma, bet žingsnis žymimas
**Praleista**, ne **Atlikta**. Sąrašas nekuria užduočių ar priminimų ir netrukdo dirbti.

<DocScreenshot name="onboarding" alt="Pirmieji žingsniai: pažangos juosta ir šeši išskleidžiami paskyros paruošimo žingsniai" caption="Žingsnius galima peržiūrėti bet kuria tvarka, o suvestinę pradžioje – paslėpti." href="/mano/onboarding" />

Pareigų ir kontaktų peržiūra aprašyta [Paskyroje ir prieigoje](/mano/paskyra-ir-prieiga#pirmieji-zingsniai),
o diegimas bei pranešimų leidimai – [Pranešimų pagrinduose](/pagrindai/pranesimai#irenginio-paruosimas).

## Techninė informacija {#technine-informacija}

- Pradžios puslapis – `ShowAdminHome.vue`; skyriai, kurių tau nereikia (pvz., institucijos, kai jų
  neturi), nerodomi, o likę užima jų vietą.
- Kad puslapis telpa telefone, planšetėje ir kompiuteryje (390, 820, 1180 ir 1440 px pločio ekranuose,
  šviesia ir tamsia tema), tikrina `AdminHomeLayoutTest`.
