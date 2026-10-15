---
doc_status: reviewed
title: Ryšiai
area: institutionLinks
models: [InstitutionLink, InstitutionTypeLink]
last_reviewed: 2026-10-07
tests:
  - tests/Feature/Services/InstitutionRelationServiceTest.php
  - tests/Feature/Admin/Management/InstitutionLinkControllerTest.php
  - tests/Feature/Migrations/ReplaceRelationshipsWithInstitutionLinksTest.php
---

# Ryšiai

Ryšys nurodo, kad vienos institucijos nariai gali matyti kitos institucijos posėdžius ir darbotvarkes.
Ryšiai lemia [institucijų prieigą](/visak/padaliniai), posėdžių sąrašą atstovavimo laiko juostoje
ir yra vaizduojami [Institucijų grafe](/visak/instituciju-grafas).

<ChangelogNote version="v3.0" date="2026-10-02" title="Ryšiai tvarkomi kortelėse">

Ryšiai kuriami ir keičiami institucijų ir institucijų tipų kortelėse, skirtuke **Ryšiai**.
Atskiros skilties **Sistema → Ryšiai** ir ryšio tipų nebėra.

</ChangelogNote>

## Rekomendacijos {#rekomendacijos}

::: tip
- Ryšį verta kurti tik tada, kai vienos institucijos atstovams iš tiesų reikia matyti kitos posėdžius.
- Kai tas pats santykis tinka visoms tam tikro tipo institucijoms, geriau kurti vieną tipų ryšį nei daug tiesioginių.
- Pašalinus ryšį, nariai iš karto nustoja matyti susijusių posėdžių darbotvarkes.
:::

## Kaip tai veikia

Ryšiai būna dviejų rūšių:

- **Tiesioginis ryšys** – tarp dviejų konkrečių institucijų.
- **Tipų ryšys** – tarp dviejų institucijų tipų. Jis galioja visoms tų tipų institucijoms.

### Kryptis ir abipusiškumas {#kryptis}

Kiekvienas ryšys turi šaltinį ir tikslą:

- šaltinio nariai mato tikslo posėdžius ir darbotvarkes;
- tikslo nariai šaltinį mato susijusių institucijų sąraše, o jo posėdžius – be darbotvarkių;
- pažymėjus **Abipusis ryšys**, abiejų pusių nariai mato vieni kitų posėdžius ir darbotvarkes.

Jei dvi institucijos susietos keliais būdais, galioja plačiausia prieiga.

### Tipų ryšio apimtis {#apimtis}

- Pagal nutylėjimą tipų ryšys galioja tame pačiame padalinyje. Pavyzdžiui, MIF studijų kolegija
  susiejama tik su MIF studijų programų komitetais.
- Įjungus **Centrinis → padaliniai**, šaltinio tipo institucijos Centriniame biure susiejamos su
  tikslo tipo institucijomis visuose padaliniuose.
- Tipas gali būti susietas ir su savimi („To paties tipo institucijos“). Abipusis ryšys tame pačiame
  padalinyje susieja, pavyzdžiui, Senato komitetus tarpusavyje. Ryšys **Centrinis → padaliniai** be
  abipusiškumo leidžia Centrinio biuro komisijai matyti padalinių komisijas, o padaliniai jos posėdžius
  mato be darbotvarkių.

### Ryšio pobūdis {#pobudis}

Pobūdis paaiškina, ką ryšys reiškia, ir rodomas sąrašuose bei grafe. Matomumui jis įtakos neturi.
Galimi pobūdžiai: *Patariamasis*, *Tvirtina sudėtį*, *Klausimai keliauja toliau*, *Kuruoja*,
*Bendradarbiauja* ir *Susijusi*.

## Veiksmai

### Tiesioginio ryšio kūrimas {#tiesioginio-rysio-kurimas}

1. Atverk institucijos kortelę ir pereik į skirtuką **Ryšiai**.
2. Skiltyje **Tiesioginiai ryšiai** spausk **Pridėti ryšį**.
3. Pasirink kitą instituciją, kryptį („Ši → kita“ arba „Kita → ši“) ir ryšio pobūdį. Jei reikia,
   pažymėk **Abipusis ryšys**.
4. Spausk **Išsaugoti**.

Ryšys rodomas abiejų institucijų kortelėse ir tvarkomas iš bet kurios jų.

### Tipų ryšio kūrimas {#tipu-rysio-kurimas}

1. Atverk institucijos tipo kortelę (**Sistema → Tipai ir kategorijos → Institucijų tipai**) ir pereik į
   skirtuką **Ryšiai**.
2. Spausk **Pridėti ryšį**.
3. Pasirink kitą tipą (arba tą patį), kryptį, pobūdį, abipusiškumą ir, jei reikia, **Centrinis → padaliniai**.
4. Spausk **Išsaugoti**.

### Ryšio keitimas ir šalinimas

Prie ryšio spausk **Redaguoti**, kad pakeistum pobūdį ar abipusiškumą. Norėdamas susieti kitą
instituciją, pakeisti kryptį ar apimtį, pašalink ryšį (**Pašalinti**) ir sukurk naują.
Šalinimas galutinis – šiukšlinė nenaudojama.

Institucijos kortelės skirtukas **Ryšiai** rodo ir visas susijusias institucijas, taip pat atsiradusias
per tipus. Jos pažymėtos „Per tipą“ ir keičiamos tipo kortelėje.

## Kas ką gali {#teises}

| Veiksmas | Studentų atstovų koordinatorius | Super Admin |
|---|---|---|
| Matyti susijusias institucijas institucijos kortelėje | ✓ | ✓ |
| Kurti, keisti ir šalinti ryšius | – | ✓ |
| Matyti ryšius Institucijų grafe | ✓ | ✓ |

Ryšius tvarkyti gali **Super Admin** arba narys, kurio rolei suteikti visos platformos ryšių leidimai.
Teisė redaguoti instituciją ar tipą ryšių tvarkyti neleidžia, nes ryšys atveria kitų institucijų duomenis.

## Pranešimai ir automatizavimas {#pranesimai}

- **El. laiškai**: apie ryšių kūrimą ar keitimą nepranešama.
- **Prieiga**: sukūrus, pakeitus ar pašalinus ryšį, priskyrus institucijai tipą ar perkėlus instituciją į
  kitą padalinį, narių prieiga ir paieška atnaujinamos be laukimo – pakanka iš naujo atverti puslapį.
- **Grafas**: pakeitimai matomi iš naujo įkėlus [Institucijų grafo puslapį](/visak/instituciju-grafas).

## Techninė informacija {#technine-informacija}

- Lentelės `institution_links` ir `institution_type_links`; modeliai `InstitutionLink`, `InstitutionTypeLink`;
  pobūdžiai – `InstitutionRelationKind`.
- Maršrutai: `institutions.links.store`, `institutionLinks.update|destroy`, `institutionTypes.links.store`,
  `institutionTypeLinks.update|destroy`; valdikliai `InstitutionLinkController`, `InstitutionTypeLinkController`.
- Leidimai: `relationships.create.*`, `relationships.update.*`, `relationships.delete.*`
  (politikos `InstitutionLinkPolicy`, `InstitutionTypeLinkPolicy`).
- Ryšius į institucijų poras išskleidžia `InstitutionRelationService`. Jo rezultatą naudoja prieigos patikros,
  atstovavimo laiko juosta, posėdžio institucijų pasirinkimas ir grafas. Rezultatas saugomas talpykloje su
  versijos žyme, kuri keičiama pasikeitus ryšiams, tipų priskyrimams, institucijos padaliniui ar padalinio tipui;
  ta pati žymė įeina į narių prieigos ir paieškos raktų talpyklas.
- Migracija `replace_relationships_with_institution_links` perkėlė senus `relationships` / `relationshipables`
  įrašus ir tipų „sibling“ nustatymus į naujas lenteles. Seni tarp padalinių galiojantys tipų ryšiai tapo
  dviem ryšiais (abiem kryptimis), kad prieiga nepasikeistų.
