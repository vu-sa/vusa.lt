---
doc_status: partial
title: Formos ir registracijos
area: forms
models: [Form, Registration]
last_reviewed: 2026-09-27
tests:
  - tests/Feature/Admin/Forms/FormAccessTest.php
  - tests/Feature/Forms/StudentRepRegistrationTest.php
---

# Formos ir registracijos

Formos – anketos ir jų atsakymai. Dvi formos turi ypatingą paskirtį: **narių registracija** ir
**studentų atstovų registracija**. Jos rodomos kaip atskiros Organizacijos skiltys tiems, kas
gali jas peržiūrėti.

Studentų atstovų registracijos formą gali atverti [studentų atstovų koordinavimo atsakomybę](/pagrindai/atsakomybes)
turintys dabartiniai pareigybės nariai ir formų skaitymo teisę savo padalinyje turintys nariai.
Koordinatorius mato tik tų VU organų registracijas, kuriuos jis koordinuoja; artimesnis
koordinatoriaus priskyrimas pakeičia bendrą padalinio priskyrimą.

Skiltis pasiekiama adresu `/mano/forms`.

::: warning Dalinis puslapis
Šiuo metu aprašyta registracijų paskirtis ir prieiga. Formų kūrimas ir atsakymų tvarkymas dar neaprašyti.
Šis puslapis dar rašomas. Kai bus baigtas, jame bus skyriai **Kaip tai veikia**, **Veiksmai**,
**Kas ką gali** ir **Pranešimai ir automatizavimas**, kaip [Rezervacijų](/rezervacijos/rezervacijos) skyriuje.
:::

## Techninė informacija {#technine-informacija}

Nurodyti testai tikrina registracijos formos prieigą ir koordinavimo apimtį.
Formų kūrimo bei atsakymų tvarkymo eiga šiame puslapyje dar neaprašyta.
