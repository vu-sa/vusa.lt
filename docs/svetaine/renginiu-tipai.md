---
doc_status: reviewed
title: Renginių tipai
area: eventTypes
models: [EventType]
last_reviewed: 2026-10-01
tests:
  - tests/Feature/Admin/Calendar/EventTypeControllerTest.php
  - tests/Feature/Api/Admin/EventTypeApiControllerTest.php
  - tests/Feature/Seeders/EventTypeSeederTest.php
  - resources/js/Features/Admin/EventTypes/__tests__/EventTypeSheetForm.component.test.ts
---

# Renginių tipai

Renginių tipai skirti viešo kalendoriaus renginiams grupuoti ir lankytojams patogiai filtruoti (pvz., „Mokymai“, „Posėdžiai“, „Šventės“, „Integracija“). Tai bendra visos organizacijos klasifikacija.

Skiltis pasiekiama adresu `/mano/eventTypes`.

## Rekomendacijos {#rekomendacijos}

::: tip
- **Aiški paskirtis:** tipą rinkis renginių grupei, pvz., „Mokymai“, o konkretaus renginio pavadinimą įrašyk pačiame renginyje.
- **Esami tipai:** prieš kurdamas naują tipą patikrink, ar tinka jau esantis. Atskirą renginio temą gali nurodyti [žyma](/svetaine/zymos).
- **Nuorodos stabilumas:** be reikalo nekeisk tipo nuorodos trumpinio, kad nereikėtų atnaujinti jau pasidalytų kalendoriaus filtravimo nuorodų.
:::

## Kaip tai veikia

- **Bendra klasifikacija:** renginių tipai yra bendri visiems VU SA padaliniams ir rodomi viešo kalendoriaus filtro pasirinkimuose.
- **Dvikalbiškumas:** kiekvienas renginio tipas turi lietuvišką ir anglišką pavadinimą bei aprašymą, todėl kalendoriaus filtrai automatiškai prisitaiko prie lankytojo pasirinktos kalbos.
- **Rikiavimas (`sort_order`):** nustato eiliškumą, kuria tvarka tipai pateikiami viešuose filtruose ir renginio kūrimo formoje.
- **Būsena (`is_active`):** leidžia laikinai paslėpti nebeaktualius tipus iš pasirinkimų sąrašo, neištrinant jų iš ankstesnių renginių istorijos.

## Veiksmai

Renginių tipus tvarkai sąrašo puslapyje, atvėręs šoninį langą.

### Naujo tipo kūrimas

1. Sąrašo viršuje spausk **Naujas renginio tipas**.
2. Dešinėje atsidariusiame lange užpildyk laukus:
   - **Pavadinimas** – įvesk pavadinimą lietuvių ir anglų kalbomis (pvz., LT: „Mokymai“, EN: „Training“).
   - **Slug** – įrašyk unikalų trumpinį mažosiomis raidėmis be tarpų ir lietuviškų simbolių (pvz., `mokymai`).
   - **Aprašymas** – neprivalomas trumpas paaiškinimas abiem kalbomis apie tai, kokie renginiai čia priskiriami.
   - **Rikiavimo tvarka** – sveikas skaičius nuo `0`. Mažesnį skaičių turintys tipai rodomi pirmi.
   - **Aktyvumas** – įjunk jungiklį, kad tipas iškart būtų pasiekiamas kalendoriuje.
3. Spausk **Išsaugoti**.

### Redagavimas

Paspausk tipo pavadinimo sąraše arba veiksmų meniu pasirink **Redaguoti**. Atsidarys šoninis langas su esamais duomenimis. Pakeitęs reikšmes spausk **Išsaugoti**.

### Šalinimas ir galutinis ištrynimas

- **Perkėlimas į šiukšlinę:** veiksmų meniu pasirink **Ištrinti**. Renginio tipas paslepiamas iš aktyvių pasirinkimų sąrašo.
- **Atkūrimas:** šiukšlinės rodinyje pasirink **Atkurti**.
- **Apsauga nuo ištrynimo:** jei renginio tipas turi bent vieną priskirtą kalendoriaus renginį (įskaitant ir esančius šiukšlinėje), sistema **blokuoja galutinį ištrynimą** (`withForceDeleteBlockers`). Lentelėje aiškiai nurodoma priežastis: „Negalima ištrinti, nes yra priskirtų renginių“. Norėdamas tipą ištrinti visam laikui, pirmiausia perkelk tuos renginius į kitą tipą.

## Kas ką gali {#teises}

Kadangi renginių tipai yra bendri visai organizacijai ir veikia visų padalinių kalendorius, juos administruoja tik centrinis biuras:

| Veiksmas | Padalinio koordinatorius | Centrinio biuro komunikacijos koordinatorius |
|---|---|---|
| Priskirti tipą renginiui | ✓ (kuriant renginį) | ✓ |
| Matyti tipų administravimo sąrašą | – | ✓ |
| Sukurti naują tipą | – | ✓ |
| Redaguoti tipus ir rikiavimo tvarką | – | ✓ |
| Ištrinti į šiukšlinę / atkurti | – | ✓ |
| Ištrinti visam laikui | – | ✓ (tik jei nėra priskirtų renginių) |

## Pranešimai ir automatizavimas {#pranesimai}

- Sukūrus, pakeitus ar pašalinus tipą jokie el. laiškai nesiunčiami.
- Renginių tipų pasikeitimas viešame kalendoriuje įsigalioja iš karto.

## Techninė informacija {#technine-informacija}

Šis skyrius skirtas administratoriams ir sistemos priežiūrai.

### Teisės

- Prieigą tikrina `EventTypePolicy`.
- Reikalingi visos platformos leidimai: `eventTypes.create.*`, `eventTypes.read.*`, `eventTypes.update.*`, `eventTypes.delete.*`.
- Padalinių pareigybės šių leidimų neturi – jos gali tik skaityti aktyvius tipus pildydamos kalendoriaus formą per `EventType::query()->orderBy('sort_order')->get()`.

### Kaip tai įgyvendinta

- Modelis: `App\Models\EventType`, palaikantis Spatie translatable savybes (`name`, `description`) ir `SoftDeletes`.
- Valdiklis: `App\Http\Controllers\Admin\EventTypeController` (palaiko tik `index`, `store`, `update`, `destroy`, `restore`, `forceDelete`).
- API valdiklis: `App\Http\Controllers\Api\Admin\EventTypeApiController`.
- Šoninis langas: `resources/js/Features/Admin/EventTypes/EventTypeSheetForm.vue`.
- Galutinio ištrynimo blokavimas įgyvendintas per `HasTanstackTables::withForceDeleteBlockers()` tikrinant `calendarEvents` ryšį.
