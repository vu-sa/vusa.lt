# Gido puslapių rengimas

Galiojančios taisyklės: [.ai/rules/docs.md](../../.ai/rules/docs.md).
Bendras balsas ir žodynas: [.ai/rules/lang.md](../../.ai/rules/lang.md).
Šis failas yra šablonų rinkinys; jis neskelbiamas svetainėje ar PDF.

Kopijuok tik temai reikalingą šabloną. `last_reviewed` įrašyk po tikro turinio patikrinimo;
jei dar netikrinai, lauką praleisk. `tests` turi nurodyti esamus failus, kurie tikrina aprašytas
elgsenos dalis. Vien testų ar sėkmingo build neužtenka būsenai `reviewed`.

## Skilties gidas

```markdown
---
title: Skilties pavadinimas
doc_status: draft
area: featureArea
models: [Model]
---

# Skilties pavadinimas

Kam ši skiltis skirta ir kaip ją atverti.

::: warning Rašoma
Šio puslapio instrukcijos dar neparuoštos.
:::

## Susitarimai {#susitarimai}
## Rekomendacijos {#rekomendacijos}
## Kaip tai veikia
## Veiksmai
## Kas ką gali {#teises}
## Pranešimai ir automatizavimas {#pranesimai}
## Techninė informacija {#technine-informacija}
```

Po įvadinio teksto eiliškumas: **Susitarimai** (organizaciniai susitarimai, pateikiami sąrašu, o
ne rėmelyje), po to **Rekomendacijos** (praktiniai patarimai, pateikiami `::: tip` rėmelyje), jei jų yra.
**Susitarimai** – tik konkretūs organizaciniai susitarimai, kurių sistema automatiškai netikrina.
**Rekomendacijos** – neprivalomi praktiniai patarimai, padedantys pasirengti darbui. Nepaversk bendro
patarimo tariama VU SA taisykle ir nepriskirk organizacijai dokumentais nepagrįstų reikalavimų,
terminų ar skaičių. Jei tokių patarimų ar susitarimų nėra, atitinkamą skyrių praleisk; nerašyk „Nėra“.
Pervadindamas esamą skyrių išsaugok jo inkarą, kad ankstesnės nuorodos liktų veikti.

Pildydamas naudok `partial` ir perspėjime įvardyk, kurios dalys jau aprašytos.
Tik toms dalims gali pridėti `tests`. Patikrinęs visą parašytą puslapį, pakeisk būseną į
`reviewed`, įrašyk tikrą datą ir pašalink rašymo perspėjimą.

## Darbo eiga

```markdown
---
title: Atlikti konkretų darbą
doc_status: draft
coverage: ignore
---

# Atlikti konkretų darbą

## Kada pradėti ir kokios prieigos reikia
## Žingsniai
## Rezultatas
## Jei nepavyko
```

Taisykles ir teisių lenteles susiek su autoritetingu skilties gidu. Jei puslapis aprašo
testuojamą funkciją, vietoje `coverage: ignore` deklaruok atitinkamą `area` ir įrodymų failus.

## Sąvoka arba darbo srities apžvalga

```markdown
---
title: Sąvoka arba darbo sritis
doc_status: draft
coverage: ignore
---

# Sąvoka arba darbo sritis

Paaiškinimas paprastais žodžiais, apimtis ir nuorodos į susijusius puslapius.
```

Pridėk tik reikalingas antraštes; visų gido skyrių čia nereikia.
Apžvalgos peržiūros data nepatvirtina visų jos nuorodomis pasiekiamų puslapių.

## Patikrinimas prieš užbaigiant

- Palygink žingsnius su dabartine sąsaja, roles su seeders, policies ir rolių testais.
- Atskirai įvardyk sistemos patikras, siūlomas reikšmes ir organizacinius susitarimus.
- Patikrink statusą, įrodymų apimtį, nuorodas ir išsaugotus antraščių inkarus.
- Paleisk `./vendor/bin/sail artisan docs:coverage --strict`.
- Sukurk PDF su `./vendor/bin/sail npm run docs:pdf`, tada svetainę su `./vendor/bin/sail npm run docs:build`.
- Pakeitęs maketą patikrink 390, 820, 1180 ir 1440 px, abi temas, klaviatūrą ir lietimą.

`docs:coverage` kredituoja deklaruotus `area` ir `models`, įskaitant juodraščius.
Jo procentas nėra baigtų puslapių procentas. Parengtį nurodo `doc_status`.
