---
doc_status: reviewed
title: Institucijų grafas
area: institutionGraph
last_reviewed: 2026-10-07
tests:
  - tests/Feature/Admin/Core/DashboardControllerTest.php
  - tests/Feature/Services/InstitutionRelationServiceTest.php
---

# Institucijų grafas

Grafas padeda suprasti, kaip susijusios institucijos ir jų tipai. Navigacijos nuoroda
**ViSAK → Institucijų grafas** rodoma turint padalinio institucijų peržiūros prieigą,
pavyzdžiui, su komunikacijos arba studentų atstovų koordinatoriaus role.
Adresas – `/mano/institutionGraph`.

## Kaip tai veikia

Viršuje galima pasirinkti vieną iš dviejų rodinių:

- **Institucijos** – institucijų mazgai ir jų ryšiai. Mazgo dydį lemia narių skaičius;
  institucijos išdėstomos grupėmis pagal padalinį.
- **Tipai** – institucijų tipai ir tarp jų nustatyti ryšiai. Mazgo dydį lemia to tipo
  institucijų skaičius.

Linijų spalvas paaiškina legenda apačioje. Institucijų rodinyje skiriami tiesioginiai, per tipus
atsirandantys ir Centrinio biuro su padaliniais siejantys ryšiai. Rodyklė žymi, kas ką mato,
dvipusės rodyklės – abipusį ryšį. Užvedus žymeklį ant ryšio rodomas jo pobūdis.

Grafas rodo ryšių struktūrą, o ne institucijos veiklos būklę ar naudotojo teises ją redaguoti.
Ryšių taisykles ir jų apimtį paaiškina [Ryšių gidas](/sistema/rysiai).

## Veiksmai

- **Priartinti / Atitolinti** keičia mastelį. **Atstatyti vaizdą** grąžina pradinį mastelį ir padėtį.
- Tempiant tuščią grafo vietą paslenkamas vaizdas, tempiant mazgą keičiama jo vieta šiame rodinyje.
- Užvedus žymeklį ant mazgo paryškinami jo kaimynai; ant ryšio – jo galai ir papildomas paaiškinimas.
- Paspaudus ryšį paryškinimas lieka. Paspausk jį dar kartą arba tuščią vietą, kad paryškinimą nuimtum.

Norėdamas pakeisti institucijos duomenis, atverk [Institucijų sąrašą](/visak/institucijos).
Tipus keisk per [Tipai ir kategorijos](/sistema/tipai), ryšius – institucijos ar tipo
kortelės skirtuke **Ryšiai** (žr. [Ryšiai](/sistema/rysiai)). Mazgų pertempimas šių duomenų nekeičia.
Tipo redagavimui rekomenduojama naudoti tipų katalogą: dabartinis dvigubas paspaudimas grafe jo neatveria.

## Kas ką gali {#teises}

Meniu nuorodą mato padalinio institucijas peržiūrintys nariai ir **Super Admin**.
Tai nėra atskiras leidimas keisti institucijas ar tipus. Redagavimą visada sprendžia
to įrašo teisės. Dalis papildomų ryšio paaiškinimų rodoma užvedus žymeklį;
ši funkcija nėra pilnas klaviatūros ar telefono įrašų redaktorius.

## Pranešimai ir automatizavimas {#pranesimai}

Grafas sudaromas iš institucijų ir ryšių duomenų. Vien peržiūra, mastelio keitimas ar mazgo
pertempimas nesiunčia pranešimų ir nekeičia išsaugotų ryšių. Pakeitus duomenis kitame
puslapyje, grafą reikia atnaujinti.

## Techninė informacija {#technine-informacija}

- `DashboardController::institutionGraph()` pateikia institucijas, narių skaičius ir abu ryšių rinkinius.
- Meniu nuorodą riboja `AdminNavigationCatalog` tikrinama `institutions.read.padalinys` teisė.
  Pats GET maršrutas turi prisijungimo apsaugą, bet valdiklis papildomos šios teisės patikros neatlieka;
  meniu matomumas nėra tiesioginio adreso autorizacijos įrodymas.
- Vaizdą generuoja `InstitutionGraph.vue`; ryšius pateikia `InstitutionRelationService` – tas pats, kuris sprendžia prieigą.
- Tipo dvigubo paspaudimo nuoroda dar naudoja pašalintą `types.edit` maršrutą.
- `DashboardControllerTest` tikrina grafo atsakymą ir narių skaičius administratoriaus paskyrai;
  `InstitutionRelationServiceTest` – ryšių sudarymą. Šie testai nepatvirtina visų grafo valdiklių ar prieigos ribojimo.
