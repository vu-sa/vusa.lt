---
doc_status: reviewed
title: Sharepoint failai
area: sharepointFiles
models: [SharepointFile, FileableFile]
last_reviewed: 2026-10-02
tests:
  - tests/Feature/Admin/Resources/SharepointFileControllerTest.php
  - tests/Feature/Api/Admin/SharepointApiControllerTest.php
  - tests/Feature/SharepointStagingProtectionTest.php
  - tests/Feature/Admin/FileableFileControllerTest.php
  - resources/js/Features/Admin/FileManager/__tests__/FilePropertiesDrawer.component.test.ts
---

# Sharepoint failai

Skiltis **Sharepoint failai** (`/mano/sharepointFiles`) skirta universiteto „Microsoft SharePoint“ dokumentų saugyklai naršyti, aplankams tvarkyti ir failams susieti su platformos įrašais.

Čia saugomi institucijų posėdžių protokolai, darbotvarkės, nuostatai ir kiti atstovavimo dokumentai, pasiekiami per tiesioginę „Microsoft 365“ integraciją.

## Kaip tai veikia

Platforma nesaugo didelių dokumentų failų savo serveryje – jie fiziškai laikomi VU „Microsoft 365“ SharePoint diske. Mano VU SA per „Microsoft Graph API“ sąsają pasiekia disko elementus ir susieja juos su vidiniais sistemos modeliais.

### Ryšys su platformos įrašais

Vietinėje duomenų bazėje saugomi failo metaduomenys ir sąsaja su platformos įrašu. Dabartinis failo įrašas apima abu; senesniems failams dar naudojamas atskiras metaduomenų modelis:
- Failas gali būti priskirtas **posėdžiui**, **institucijai** ar kitam objektui.
- Kai dokumentas priskiriamas posėdžiui, institucijos atstovai jį mato tiesiai posėdžio kortelėje.

### Viešos ir privačios nuorodos

Failo prieigą nustato SharePoint leidimai. Jei dokumentas turi būti pasiekiamas viešai,
savybių skydelyje gali sukurti viešą nuorodą. Ją turintis žmogus gali atverti dokumentą
neprisijungęs prie „Microsoft“. Prieš kurdamas nuorodą patikrink, ar dokumentą galima viešinti.

### Apsauga ne gamybinėse aplinkose

Bandomojoje (*staging*) aplinkoje failų keitimas ir šalinimas blokuojamas, kai įjungtas
skaitymo režimas arba pasirinkta neleistina svetainė ar gamybinis diskas. Veiksmas nėra
imituojamas ir automatiškai nenukreipiamas į kitą diską. Ši apsauga netaikoma visoms kūrimo
ar testavimo aplinkoms.

## Veiksmai

### Failų naršyklė (`/mano/sharepointFiles`)

Puslapyje veikia failų tvarkyklė:
- **Judėjimas tarp aplankų**: pradinis aplankas yra `General`. Dukart spustelėk aplanką, kad į jį užeitum.
- **Kelio juosta** (viršuje): rodo visą kelią iki esamo aplanko. Spustelėk bet kurią kelio dalį, kad sugrįžtum į aukštesnį lygį.
- **Paieška**: įvesk pavadinimo fragmentą paieškos lauke, norėdamas atsirinkti failus esamame aplanke.
- **Atnaujinti**: paspausk atnaujinimo mygtuką, jei failai SharePoint diske buvo neseniai pakeisti iš išorės.

### Naujo aplanko sukūrimas

1. Aplankų lange paspausk **Sukurti aplanką**.
2. Įvesk naujo aplanko pavadinimą.
3. Patvirtink – aplankas bus sukurtas tiesiogiai SharePoint diske esamo aplanko viduje.

### Failo savybių peržiūra

Pasirink failą, kad dešinėje atvertum savybių skydelį:
- Matomas failo pavadinimas, dydis, plėtinys, paskutinio pakeitimo laikas ir vieta.
- Skydelyje galima sugeneruoti viešą atsisiuntimo nuorodą.

### Failo šalinimas

1. Pasirink failą ir atverk jo savybių skydelį.
2. Paspausk **Ištrinti** ir patvirtink veiksmą.
3. Sėkmingai ištrynus dabartinį failą iš SharePoint disko, pašalinamas jo vietinis įrašas. Jei failo diske jau nėra, vietinis įrašas taip pat pašalinamas. Kitai integracijos klaidai įvykus, įrašas išlieka ir pažymimas kaip ištrintas išorėje; ši žyma savaime neįrodo, kad failo diske nebėra.

## Kas ką gali {#teises}

| Veiksmas | Studentų atstovas | Studentų atstovų koordinatorius | Centrinio biuro studentų atstovų koordinatorius | Super Admin |
|---|---|---|---|---|
| Matyti failų skiltį ir naršyti aplankus | – | ✓ | ✓ | ✓ |
| Kurti aplankus ir viešas nuorodas | ✓ | ✓ | ✓ | ✓ |
| Šalinti dabartinį susietą failą | Pagal susieto įrašo redagavimo teisę | Pagal susieto įrašo redagavimo teisę | Pagal susieto įrašo redagavimo teisę | ✓ |

Lentelėje įvardytos sistemoje apibrėžtos rolės. **Studentų atstovas** turi failų kūrimo
teisę, bet bendros failų naršyklės neatveria; failus pasiekia per susietą įrašą. Prisijungimas be atitinkamos rolės ar
atskirai suteiktų teisių failų naršyklės neatveria. Dabartinio failo šalinimo teisė tikrinama
pagal jo savininką, pavyzdžiui, posėdį. Senesnių failų šalinimui taikoma savininko šalinimo teisė.

## Pranešimai ir automatizavimas {#pranesimai}

- **Sinchronizavimas fone**: dokumentų importas ir metaduomenų atnaujinimas iš SharePoint vykdomas foninėmis eilės užduotimis.
- **Išorinio trynimo atpažinimas**: jei failas pašalinamas pačioje „Microsoft SharePoint“ sistemoje, platforma klaidą atpažįsta ir pažymi vietinį įrašą kaip ištrintą išorėje ir pateikia pranešimą apie nepasiekiamą failą.

## Rekomendacijos {#susitarimai}

- Laikykis nustatytos padalinių ir institucijų aplankų hierarchijos, kad dokumentai nepasimestų.
- Netrink ir nepervadink pagrindinių šakninių aplankų (`General`, padalinių santrumpų aplankų), nes jie susieti su automatinėmis posėdžių dokumentų kėlimo taisyklėmis.

## Techninė informacija {#technine-informacija}

- Valdikliai:
  - `SharepointFileController` (`sharepointFiles.index`, `sharepointFiles.destroy`, `createFolder`, `getDriveItemPublicLink`, `createPublicPermission`).
  - `SharepointApiController` (`attachFileableFilesToDriveItems`, `fileableFiles`).
- Integracijos tarnyba: `SharepointGraphService` (naudoja `microsoft/microsoft-graph` paketą ir `vusa_drive_id` disko ID).
- Prieigos politika: `SharepointFilePolicy` (trynimas tikrinamas per `$sharepointFile->fileables->first()?->fileable`).
- Modeliai: `SharepointFile`, `FileableFile`, `SharepointFileable`.
- Dabartiniai failai: `FileableFileController` ir `FileableFilePolicy`; senesni metaduomenys: `SharepointFile`.
- Bandomosios aplinkos integracijos apsauga: `SharepointGraphService::shouldRestrictSharepointOperations()`; `StagingReadOnlyMode` atskirai riboja platformos užklausas.
