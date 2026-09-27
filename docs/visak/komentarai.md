---
title: Komentarai
area: comments
models: [Comment]
last_reviewed: 2026-09-27
tests:
  - tests/Feature/Admin/Discussions/CommentAuthorizationTest.php
  - tests/Feature/Admin/Discussions/CommentApiTest.php
  - resources/js/Components/Discussions/__tests__/DiscussionPanel.component.test.ts
---

# Komentarai

Komentarus gali palikti daugelis naudotojų įvairiose platformos vietose: posėdžiuose, problemose,
rezervacijų ištekliuose. Komentaras priskiriamas jį parašiusiam žmogui ir įrašui, prie kurio jis
paliktas. Komentaruose galima paminėti kitus naudotojus – jie gaus pranešimą.

::: warning Rašoma
Šis puslapis dar rašomas. Kai bus baigtas, jame bus skyriai **Kaip tai veikia**, **Veiksmai**,
**Kas ką gali** ir **Pranešimai ir automatizavimas**, kaip [Rezervacijų](/rezervacijos/rezervacijos) skyriuje.
:::

## Kas ką gali {#teises}

Komentavimas seka patį įrašą: jei matai posėdį, problemą ar kitą įrašą, gali jį komentuoti.

| Veiksmas | Bet kuris narys | Galintis redaguoti įrašą | Rolė su komentarų trynimu |
|---|---|---|---|
| Skaityti ir rašyti komentarus, reaguoti, pažymėti išspręstu | ✓, jei mato įrašą | ✓ | ✓, jei mato įrašą |
| Redaguoti ir ištrinti savo komentarą | ✓ | ✓ | ✓ |
| Ištrinti kito žmogaus komentarą | – | ✓ | ✓, savo padalinio įrašuose arba visur |

Ištrintas komentaras neišsaugomas: jo tekstas, paminėjimai, reakcijos, balsai ir autorius
panaikinami visam laikui, o jo vietoje gijoje lieka užrašas **Komentaras ištrintas**, kad atsakymai į
jį neprarastų konteksto. Tokio komentaro atkurti negalima. Kito žmogaus komentarą redaguoti negali niekas. Komentarų prie SharePoint failų ir pagalbos
užklausų, kurie nepriklauso padaliniui, kitų žmonių trinti gali tik visur galiojanti komentarų
trynimo teisė.

## Techninė informacija {#technine-informacija}

- Teises sprendžia `CommentPolicy`: `view`, `resolve`, `react` – `view` ant įrašo; `update` – tik
  autorius; `delete` – autorius, `update` ant įrašo, `comments.delete.padalinys` (įrašo padalinys)
  arba `comments.delete.*`.
- `comments.create|read|update.*` ir `comments.delete.own` nesukuriamos – jos nieko nepridėtų
  (žr. [Ką gali kiekvienas narys](/pagrindai/teises#bazine-prieiga)).
- Komentarai neturi minkštojo ištrynimo. `Comment::erase()` ištrina tekstą, paminėjimus, reakcijas,
  balsus ir autorių, o eilutė lieka su `erased_at` kaip vietos žymė, kad atsakymai liktų gijoje.
  Todėl ir `comments.forceDelete.*` nėra.
