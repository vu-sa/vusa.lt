---
doc_status: reviewed
title: Atstovų rodikliai
area: repMetrics
last_reviewed: 2026-10-02
tests:
  - tests/Feature/Admin/Core/RepMetricsTest.php
  - resources/js/Pages/Admin/__tests__/ShowRepMetrics.component.test.ts
---

# Atstovų rodikliai

Skiltis **Atstovų rodikliai** (`/mano/rep-metrics`) skirta studentų atstovavimo kokybei ir posėdžių fiksavimo greičiui stebėti.

Čia pateikiami apibendrinti rodikliai, rodantys, ar atstovų darbas tampa sklandesnis, ar posėdžiai fiksuojami laiku ir ar grįžtamojo ryšio ciklas trumpėja.

## Kaip tai veikia

Skaičiai imami tiesiogiai iš sistemos duomenų bazės (posėdžių, darbotvarkių klausimų, balsavimų, užduočių bei naudotojų aktyvumo žymų), be jokių išorinių sekimo priemonių. Pirmi penki rodikliai turi **2026-09-17** pradinę reikšmę ir tikslą. Šeštasis jų neturi.

### Pagrindiniai rodikliai

Sistema matuoja šešis rodiklius:

1. **Posėdis užfiksuotas per 7 d.** – dalis posėdžių, kurie buvo užregistruoti sistemoje iki posėdžio pradžios arba per 7 dienas po jo. Vėliau užregistruoti posėdžiai įskaičiuojami į bendrą skaičių, bet nelaikomi užfiksuotais laiku. Skaičiuojami tik jau įvykę posėdžiai.
2. **Darbotvarkės klausimai su balsavimo informacija** – darbotvarkės klausimų dalis, prie kurių užfiksuotas priimtas sprendimas arba studentų atstovo balsas.
3. **Įvykdytos užduotys** – per pastaruosius 12 mėnesių sukurtų užduočių įvykdymo procentas. Įtraukiamos visos užduotys, taip pat rankinės ir rezervacijų.
4. **Įvykdyti priminimai „ar vyko posėdis?“** – dalis periodiškumo patikros užduočių, į kurias atstovai atsakė užfiksuodami posėdį arba patvirtindami, kad jis nevyko.
5. **Atstovai, aktyvūs per pastarąsias 30 d.** – atstovų, kurie per pastarąsias 30 dienų prisijungė ar atliko veiksmą platformoje, dalis. Tai momentinis rodiklis, neturintis istorinio kitimo grafiko.
6. **Posėdžius užfiksavo pats institucijos atstovas** – dalis posėdžių su žinomu kūrėju, kuriuos užfiksavo tos institucijos studentų atstovas. Jo pareigybės laikotarpis turi apimti posėdžio datą. Posėdžiai, kurių kūrėjas nežinomas, į skaičiavimą neįtraukiami; koordinatorius taip pat įskaitomas, jei tuo metu buvo institucijos atstovas.

### Užduotys pagal tipą

Atskiroje lentelėje pateikiamas užduočių atlikimo efektyvumas pagal jų rūšis:
- **Rankinės** – nario rankiniu būdu sukurtos užduotys;
- **Rezervacijos tvirtinimas**, **išdavimas**, **grąžinimas** – daiktų skolinimo užduotys;
- **Darbotvarkės sukūrimas** ir **užpildymas** – pasirengimo posėdžiams užduotys;
- **Ar vyko posėdis?** – automatiniai priminimai pagal institucijos periodiškumą.

Kiekvienam tipui rodoma, kiek užduočių sukurta, kiek įvykdyta, atlikimo procentas ir atlikimo laiko mediana dienomis.

Kadangi ataskaita analizuoja 12 mėnesių duomenis, puslapyje taikomas atidėtas įkėlimas: pirmiausia atveriamas puslapis su duomenų įkėlimo vietomis, tada atskira užklausa įkeliama ataskaita. Įkėlimo trukmė priklauso nuo duomenų ir serverio.

## Veiksmai

### Rodiklių suvestinės peržiūra

Viršutinėje lentelėje matysi visų šešių rodiklių reikšmes:
- **Dabar** – dabartinis procentas;
- **Pradžioje** – bazinė reikšmė 2026-09-17 dieną;
- **Tikslas** – organizacijos išsikeltas tikslas;
- **Būsena** – automatinis įvertinimas: *Tikslas pasiektas*, *Geriau nei pradžioje*, *Blogiau nei pradžioje*, *Matuojama* arba *Nėra duomenų*. Šeštajam rodikliui pradinė ir tikslo reikšmės nerodomos.

### Kitimo grafikas

1. Skiltyje **Kaip keitėsi** pasirink vieną iš rodiklio parinkčių virš grafiko.
2. Grafike pamatysi reikšmių kitimą per pastaruosius 12 mėnesių ir, jei rodiklis turi tikslą, brūkšnine linija pažymėtą tikslo reikšmę. Aktyvių atstovų rodiklis istorinio grafiko neturi.
3. Viršuje pateikiama tekstinė santrauka su pirmojo ir paskutinio matavimo reikšmėmis bei kitimo kryptimi.

### Užduočių analizė

Skiltyje **Užduotys pagal tipą** patikrink, kurios užduotys atliekamos greičiausiai, o kur susidaro didžiausia atlikimo laiko mediana dienomis.

## Kas ką gali {#teises}

| Veiksmas | Studentų atstovas | Studentų atstovų koordinatorius | Narys su rolių peržiūros teise | Super Admin |
|---|---|---|---|---|
| Matyti rodiklių skiltį | – | – | ✓ | ✓ |
| Matyti tendencijų grafiką ir užduočių statistiką | – | – | ✓ | ✓ |

Skiltis skirta platformos ir organizacijos lygmens procesų priežiūrai, todėl ją atverti gali tik nariai, turintys teisę peržiūrėti roles.

## Pranešimai ir automatizavimas {#pranesimai}

- **Automatinis skaičiavimas**: rodikliai generuojami automatiškai pagal narių atliekamus veiksmus ir posėdžių fiksavimą. Papildomai pildyti ataskaitų nereikia.

## Rekomendacijos {#susitarimai}

- Rodiklius naudok atstovavimo spragoms pastebėti ir pagalbai planuoti.
- Prieš vertindamas procentą, patikrink jo imtį ir duomenų pilnumą: neužfiksuotas veiksmas nebūtinai reiškia, kad jis nebuvo atliktas.

## Techninė informacija {#technine-informacija}

- Valdiklis: `RepMetricsController` (`GET /mano/rep-metrics`).
- Prieigos apsauga: `handleAuthorization('viewAny', Role::class)`.
- Duomenų surinkimas: `GetRepOutcomeMetrics::execute(12)` su `Inertia::defer()` atidėtu perdavimu.
- Konsolės komanda: `RepMetricsCommand` (`./vendor/bin/sail artisan metrics:reps`).
- Rodikliai skaičiuojami iš `meetings`, `agenda_items`, `votes`, `tasks` ir `users.last_action`.
