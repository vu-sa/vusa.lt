---
doc_status: reviewed
title: Žymos
area: tags
models: [Tag]
last_reviewed: 2026-10-01
tests:
  - tests/Feature/Admin/Content/TagControllerTest.php
  - tests/Feature/Admin/Content/PolymorphicTaggingTest.php
  - tests/Feature/Admin/Content/NewsTagsTest.php
---

# Žymos

Žymos – etiketės, skirtos turiniui grupuoti ir susieti: naujienoms, puslapiams bei kalendoriaus renginiams. Žymos padeda lankytojams rasti visą su viena tema ar iniciatyva susijusią informaciją nepriklausomai nuo to, kuris padalinys ją paskelbė.

Skiltis pasiekiama adresu `/mano/tags`.

## Kaip tai veikia

### Skirtingų įrašų žymėjimas

Žyma gali būti priskirta kelių rūšių įrašams:

- Naujienoms ([Naujienos](/svetaine/naujienos)).
- Puslapiams ([Puslapiai](/svetaine/puslapiai)).
- Renginiams ([Kalendorius](/svetaine/kalendorius)).

Kai padalinio redaktorius rašo naujieną ar kuria renginį, formoje esančiame žymų laukelyje jis pasirenka atitinkamas žymas iš bendro katalogo.

### Temos puslapis {#temos-puslapis}

Žyma, pažymėta kaip tema (`is_topic = true`), turi atskirą viešą puslapį adresu `/tema/{alias}` (pvz., `/tema/stovykla`, `/tema/rinkimai`). Šiame puslapyje automatiškai surenkama ir pateikiama:

- Paskelbtos naujienos su šia žyma (naujausios viršuje).
- Aktyvūs puslapiai su šia žyma.
- Artėjantys kalendoriaus renginiai.

Tai leidžia vienoje vietoje pristatyti projektus ar ilgalaikes kampanijas.

### Žymų sujungimas {#sujungimas}

Jei sistemoje atsirado kelios panašios ar besidubliuojančios žymos (pvz., „Mokymai“, „mokymai“, „mokymai-2026“), jas galima **sujungti į vieną**:

1. Pasirenkama tikslinė žyma, kuri išliks.
2. Pasirenkamos pasikartojančios žymos, kurias norima prijungti.
3. Sistema automatiškai perkelia visus susietus straipsnius, puslapius ir renginius prie tikslinės žymos.
4. Pasikartojančios žymos automatiškai perkeliamos į šiukšlinę, kad nebeklaidintų redaktorių.

## Veiksmai

### Žymų sąrašas ir paieška

Sąraše (`/mano/tags`) pateikiamos visos sistemos žymos:

- Matomas pavadinimas, trumpinys (`alias`), aprašymas ir ar žyma yra tema.
- Galima ieškoti pagal pavadinimą, aprašymą ar alias.

### Naujos žymos kūrimas

Puslapio viršuje spausk **Nauja žyma** (arba eik adresu `/mano/tags/create`):

1. **Pavadinimas** – įrašyk žymos pavadinimą lietuvių ir anglų kalbomis.
2. **Alias** – įrašyk unikalų URL trumpinį mažosiomis raidėmis (pvz., `atstovavimas`).
3. **Aprašymas** – trumpas temos paaiškinimas (rodomas temos puslapio viršuje).
4. **Temos puslapis** – pažymėk varnelę, jei nori, kad žyma turėtų viešą temos puslapį `/tema/{alias}`.
5. Spausk **Išsaugoti**.

### Žymų sujungimas

1. Žymų sąrašo veiksmų juostoje pasirink **Sujungti žymas**.
2. Pasirink pagrindinę žymą, kurią nori išlaikyti.
3. Pažymėk vieną ar kelias besidubliuojančias žymas, kurias nori įlieti.
4. Patvirtink veiksmą. Visi ryšiai bus atnaujinti, o įlietos žymos – perkeltos į šiukšlinę.

### Redagavimas ir šalinimas

- Norėdamas pakeisti žymos pavadinimą ar įjungti temos puslapį, sąraše paspausk žymos pavadinimo arba pasirink **Redaguoti**.
- Veiksmų meniu pasirinkęs **Ištrinti**, perkelk žymą į šiukšlinę.
- Šiukšlinėje žymas galima atkurti arba ištrinti visam laikui.

## Kas ką gali {#teises}

| Veiksmas | Padalinio koordinatorius | Centrinio biuro komunikacijos koordinatorius |
|---|---|---|
| Priskirti žymą savo naujienai ar renginiui | ✓ | ✓ |
| Matyti žymų administravimo sąrašą | – | ✓ |
| Kurti naujas žymas ir temos puslapius | – | ✓ |
| Redaguoti žymas | – | ✓ |
| Sujungti besidubliuojančias žymas | – | ✓ |
| Trinti į šiukšlinę / atkurti | – | ✓ |

::: info Centralizuotas žymynas
Žymynas yra bendras visai organizacijai, kad skirtingi padaliniai nekurtų dešimčių besidubliuojančių žymų tiems patiems dalykams. Jei tavo padaliniui trūksta konkrečios temos žymos, kreipkis į Centrinio biuro komunikacijos koordinatorių.
:::

## Pranešimai ir automatizavimas {#pranesimai}

- **Automatinis ryšių perkėlimas:** sujungus žymas, visi turinio elementai persiejami automatiškai, o jų vieši puslapiai iškart pradeda rodyti atnaujintą informaciją.
- **Temos puslapio atsinaujinimas:** kai paskelbta naujiena ar kalendoriaus renginys pažymimas žyma, jis iškart atsiranda atitinkamos temos puslapyje `/tema/{alias}`.

## Techninė informacija {#technine-informacija}

Šis skyrius skirtas administratoriams ir sistemos priežiūrai.

### Teisės

- Žymų teises tikrina `TagPolicy`.
- Kadangi žymos yra visos platformos įrašai, `TagPolicy` tikrina tik visos platformos leidimus: `tags.read.*`, `tags.create.*`, `tags.update.*`, `tags.delete.*`.
- Šiuos leidimus turi Centrinio biuro komunikacijos koordinatorius ir pagrindinis administratorius.

### Kaip tai įgyvendinta

- Modelis: `App\Models\Tag`, naudojantis `SoftDeletes` ir Spatie translatable (`name`, `description`).
- Polimorfinis ryšys: realizuotas per `Illuminate\Database\Eloquent\Relations\MorphToMany` (`taggables` lentelėje).
- Valdiklis: `App\Http\Controllers\Admin\TagController`.
- Žymų sujungimas: valdiklio metodas `processMergeTags()` perkelia ryšius iš `merged_tag_ids` į `target_tag_id` ir atlieka `delete()` prijungtoms žymoms.
- Viešasis temos puslapis: maršrutas `/tema/{alias}` atvaizduojamas per `App\Http\Controllers\Public\PublicPageController::topic()`.
