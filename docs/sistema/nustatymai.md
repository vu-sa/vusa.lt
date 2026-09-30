---
title: Nustatymai
area: settings
last_reviewed: 2026-09-27
tests:
  - tests/Feature/Admin/Settings/CadenceControllerTest.php
  - tests/Feature/Admin/People/CadenceResolutionTest.php
  - tests/Feature/Admin/People/ResolveCadenceForInstitutionTest.php
---

# Nustatymai

Sistemos ir formų nustatymai. Skiltis matoma tik turintiems nustatymų tvarkymo prieigą (pvz., platformos administratoriams).

Skiltis pasiekiama adresu `/mano/settings`.

::: warning Rašoma
Šis puslapis dar rašomas. Kai bus baigtas, jame bus skyriai **Kaip tai veikia**, **Veiksmai**,
**Kas ką gali** ir **Pranešimai ir automatizavimas**, kaip [Rezervacijų](/rezervacijos/rezervacijos) skyriuje.
:::

## Kadencijos {#kadencijos}

**Kadencija** – laikotarpis, kuriam renkami nariai, dažniausiai nuo liepos 1 d. iki kitų metų
birželio 30 d. Kadencijos tvarkomos adresu `/mano/settings/cadences`.

- **Bendros VU SA kadencijos** galioja visoms institucijoms. Jas kurti, keisti ir trinti gali super
  administratorius ir rolė, kuriai suteikta nustatymų prieiga (**Nustatymai → Autorizacijos
  nustatymai**). Dvi bendros kadencijos negali prasidėti tą pačią dieną.
- **Institucijos kadencijos** – kai institucija renkama kitu ritmu. Jas savo padalinio institucijoms
  kuria **Komunikacijos koordinatorius** ir **Studentų atstovų koordinatorius** (Centrinio biuro
  koordinatoriai – visoms). Kitų padalinių institucijų ir bendrų kadencijų jie keisti negali. Jei
  institucija turi bent vieną savą kadenciją, bendros jai **visai nebetaikomos**.
- Kadencijos ribą galima susieti su **posėdžiu** (pvz., rinkimų konferencija): tada jos data imama iš
  posėdžio ir pasikeičia, kai perkeliamas posėdis.
- Numatytosios pradžios ir pabaigos dienos (mėnuo ir diena) nurodomos tame pačiame puslapyje ir
  siūlomos kuriant naują kadenciją.
- Per du metus trunkanti kadencija vadinama abiem metais (pvz., „2025–2026“).

Kadencijas naudoja [Laikotarpių tvarkyklė](/visak/pareigybiu-laikotarpiai#kadencijos) (fonas,
pritraukimas, lygiavimas ir pasiūlymai) ir [pareigybių atnaujinimas](/organizacija/pareigybiu-atnaujinimas).

## Techninė informacija {#technine-informacija}

- Kadencijas tvarko `CadenceController`; institucijos kadencijai reikia `update` tai institucijai
  (`institutions.update`), bendrai – `SettingsSettings::canUserManageSettings()`.
- Kuri kadencija taikoma institucijai, sprendžia `ResolveCadenceForInstitution` (pagal tai, kuri
  kadencija apima datą), o pareigybėms – `ResolveCadenceForDuty`.
