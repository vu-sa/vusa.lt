---
doc_status: partial
title: Greitosios nuorodos
area: quickLinks
models: [QuickLink]
last_reviewed: 2026-10-01
tests:
  - tests/Browser/PublicInitialRenderTest.php
  - tests/Feature/Public/PublicAssetsTest.php
  - resources/js/Components/Public/Nav/__tests__/QuickLink.component.test.ts
  - resources/js/Utils/__tests__/mountTranslatedApp.test.ts
---

# Greitosios nuorodos

Greitosios nuorodos rodomos po pagrindiniu meniu kiekviename puslapyje. Kiekvienas padalinys turi
savas nuorodas, o lietuviškam ir angliškam puslapiui jos nustatomos atskirai.

Skiltis pasiekiama adresu `/mano/quickLinks`.

## Rodymas svetainėje {#rodymas}

<ChangelogNote version="v2.35" date="2026-10-01" title="Meniu paruošiamas prieš pirmą rodymą" />

Nuorodas ir jų piktogramas svetainė pateikia kartu su puslapiu. Piktogramos pasirinkimo keisti
nereikia. Jei piktograma dar nepalaikoma vietiniame rinkinyje, ji įkeliama atskirai; nuoroda veikia
ir be jos.

Angliškame puslapyje meniu vertimai paruošiami prieš jį parodant, taip pat kai esi prisijungęs.
Padalinio logotipas pradedamas įkelti iš anksto, pagal dabartinį padalinį ir puslapio kalbą.

Šiame gide aprašytas rodymas svetainėje; nuorodų redagavimo eiga ir teisės dar neaprašytos.

::: warning Rašoma
Šis puslapis dar rašomas. Kai bus baigtas, jame bus skyriai **Kaip tai veikia**, **Veiksmai**,
**Kas ką gali** ir **Pranešimai ir automatizavimas**, kaip [Rezervacijų](/rezervacijos/rezervacijos) skyriuje.
:::
