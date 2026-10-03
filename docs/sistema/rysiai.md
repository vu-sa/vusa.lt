---
doc_status: reviewed
title: Ryšiai
area: relationships
models: [Relationship, Relationshipable]
last_reviewed: 2026-10-02
tests:
  - tests/Feature/Admin/Content/RelationshipControllerTest.php
  - tests/Feature/Services/RelationshipServiceTest.php
---

# Ryšiai

Skiltis **Ryšiai** (`/mano/relationships`) skirta institucijų ir jų tipų tarpusavio santykiams aprašyti.

Ryšiais nustatoma, kaip institucijos bendradarbiauja, atsiskaito viena kitai ar dalijasi atstovavimo informacija. Šie ryšiai lemia institucijų atstovų prieigos teises prie posėdžių bei darbotvarkių ir yra vizualizuojami [Institucijų grafe](/visak/instituciju-grafas).

## Kaip tai veikia

Ryšių sistemą sudaro dvi dalys: **ryšio tipas** ir konkrečios **įrašų jungtys**.

1. **Ryšio tipas** – santykio apibrėžimas, turintis pavadinimą, unikalią techninę žymę ir aprašymą (pvz., *Atsiskaito institucijai*, *Kuruoja veiklą*).
2. **Įrašų jungtys** – konkrečios sąsajos tarp dviejų platformos objektų. Jungtys gali būti sudaromos tik tarp šių rūšių modelių:
   - **Institucija** – tiesioginis ryšys tarp dviejų konkrečių institucijų.
   - **Institucijos tipas** – ryšys tarp institucijų tipų, automatiškai galiojantis visoms tiems tipams priklausančioms institucijoms.

### Jungties kryptis ir apimtis

Kiekviena jungtis turi šaltinio ir tikslo įrašus bei papildomus parametrus:

- **Abipusis ryšys**:
  - *Ne* (vienpusis) – ryšys nukreiptas iš šaltinio į tikslą. Šaltinio institucijos atstovai mato tikslo institucijos posėdžius ir klausimus, bet tikslo institucija šaltinio duomenų nemato.
  - *Taip* (abipusis) – abi institucijos laiko viena kitą partnerėmis ir turi abipusį matomumą.
- **Tipų ryšio apimtis**: taikoma, kai jungtis sudaroma tarp institucijų tipų:
  - *Padalinio viduje* – ryšys galioja tik to paties padalinio institucijoms (numatytoji reikšmė). Pavyzdžiui, MIF programų komitetas atsiskaito tik MIF tarybai.
  - *Tarp padalinių* – ryšys galioja ir tarp skirtingų padalinių institucijų (pvz., Centrinio biuro komitetas atsiskaito visų padalinių taryboms).

## Veiksmai

### Ryšių sąrašas (`/mano/relationships`)

Sąraše pateikiami ryšių tipai, jų žymės ir aprašymai.
- Naudok paieškos laukelį, norėdamas atsirinkti ryšį pagal pavadinimą ar žymę.
- Rikiuok ryšius pagal pavadinimą (A–Z arba Z–A).
- Pasirink ryšio pavadinimą, kad atvertum jo redagavimo puslapį.

### Naujo ryšio tipo kūrimas

1. Ryšių sąrašo viršuje paspausk **Naujas ryšys**.
2. Įvesk **Pavadinimą** (pvz., *Atskaitinga institucija*).
3. Įvesk unikalią **Techninę žymę** (pvz., `atskaitinga-institucija`).
4. Įvesk **Aprašymą**, paaiškinantį, kam šis santykis naudojamas.
5. Paspausk **Išsaugoti**.

### Jungčių kūrimas ir tvarkymas (`/mano/relationships/{id}`)

Atvėręs ryšio kortelę:

1. Pereik į skirtuką **Susieti įrašai**. Čia rodomas esamų jungčių sąrašas su šaltinio ir tikslo įrašais, krypties rodykle bei žymomis.
2. Paspausk **Sukurti jungtį** (arba **Sukurti pirmąją jungtį**):
   - Pasirink **Modelio rūšį**: *Institucija* arba *Institucijos tipas*.
   - Pasirink **Šaltinį** (iš kurio išeina ryšys).
   - Pasirink **Tikslą** (į kurį nukreiptas ryšys).
   - Pažymėk langelį **Abipusis ryšys**, jei santykis lygiavertis.
   - Jei pasirinkai institucijų tipus, nurodyk **Apimtį**: *Padalinio viduje* arba *Tarp padalinių*.
   - Paspausk **Sukurti**.
3. Norėdamas pakeisti esamos jungties abipusiškumą ar apimtį, prie jungties paspausk meniu **⋯** ir pasirink **Redaguoti**.
4. Norėdamas pašalinti jungtį, meniu **⋯** pasirink **Šalinti**.

### Ryšio redagavimas ir šalinimas

- Norėdamas pakeisti ryšio pavadinimą, žymę ar aprašymą, ryšio kortelėje paspausk **Redaguoti**.
- Norėdamas pašalinti visą ryšio tipą, veiksmų meniu pasirink **Šalinti**.
  Patvirtinus langą **Šalinti ryšį?**, ryšys ir visos su juo susietos įrašų jungtys ištrinamos **iškart ir negrįžtamai** (šiukšlinė nenaudojama).

## Kas ką gali {#teises}

| Veiksmas | Studentų atstovų koordinatorius | Super Admin |
|---|---|---|
| Matyti ryšių skiltį ir sąrašą | – | ✓ |
| Kurti, redaguoti ir trinti ryšių tipus | – | ✓ |
| Kurti, redaguoti ir trinti modelių jungtis | – | ✓ |
| Matyti ryšių rezultatą Institucijų grafe | ✓ | ✓ |

Ryšių administravimas yra platformos lygmens veiksmas, prieinamas **superadministratoriui** arba nariui, kurio rolei suteikti atitinkami visos platformos ryšių leidimai.

## Pranešimai ir automatizavimas {#pranesimai}

- **El. laiškai**: jokie pranešimai el. paštu apie ryšių kūrimą ar keitimą nesiunčiami.
- **Talpyklos atnaujinimas**: sukūrus, atnaujinus ar ištrynus jungtį, sistema išvalo susijusių institucijų ryšių talpyklą. Tiesioginių institucijų jungčių pakeitimai taip pat išvalo susijusių narių institucijų prieigos ir paieškos raktų talpyklas. Tipų jungčių pakeitimams toks narių talpyklų atnaujinimas nėra užtikrintas.
- **Grafas**: atliktus jungčių pakeitimus pamatysi iš naujo įkėlęs [Institucijų grafo puslapį](/visak/instituciju-grafas).

## Rekomendacijos {#susitarimai}

- Techninė žymė rašoma mažosiomis raidėmis, žodžius skiriant brūkšneliais.
- Ryšius kurk tik tiems institucijų santykiams, kurie turi organizacinę atskaitomybės ar bendradarbiavimo reikšmę.
- Prieš šalindamas ryšio tipą, patikrink susietų įrašų kortelę: pašalinus jungtis, institucijų atstovai gali prarasti teisę matyti susijusių posėdžių darbotvarkių klausimus.

## Techninė informacija {#technine-informacija}

- Valdiklis: `RelationshipController`, maršrutai `relationships.*` (`storeModelRelationship`, `updateModelRelationship`, `deleteModelRelationship`).
- Politikos: `RelationshipPolicy`, `RelationshipablePolicy`.
- Leidimai: `relationships.read.*`, `relationships.create.*`, `relationships.update.*`, `relationships.delete.*` bei `relationshipables.*.*`.
- Leistini modeliai: `AllowedRelationshipablesEnum` (`INSTITUTION` → `Institution::class`, `INSTITUTION_TYPE` → `InstitutionType::class`). Kitos modelių klasės atmetamos validuojant užklausas.
- Ryšių duomenų gavimas ir prieigos vertinimas: `RelationshipService` (`getRelatedInstitutionsCached`, `getRelatedInstitutionsFlat`).
- Talpyklos valdymas: `RelationshipableObserver` iškviečia `RelationshipService::clearCacheForRelationshipable`, `InstitutionAccessService` ir `TypesenseScopedKeyService` valymą.
- `Relationship` ir `Relationshipable` nenaudoja `SoftDeletes` – trynimas yra galutinis tiesioginis SQL `delete`.
