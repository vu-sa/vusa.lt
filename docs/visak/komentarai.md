---
doc_status: reviewed
title: Komentarai
area: comments
models: [Comment]
last_reviewed: 2026-09-30
tests:
  - tests/Feature/Admin/Discussions/CommentAuthorizationTest.php
  - tests/Feature/Admin/Discussions/CommentApiTest.php
  - tests/Feature/Admin/Discussions/CommentModelTest.php
  - tests/Feature/Admin/Discussions/PollVoteApiTest.php
  - tests/Feature/Notifications/CommentNotificationTest.php
  - resources/js/Components/Discussions/__tests__/DiscussionPanel.component.test.ts
---

# Komentarai

Komentaruose galima aptarti įrašą su kolegomis, užduoti klausimą ar suderinti kitą veiksmą.
Komentarų skydelis rodomas posėdžiuose, darbotvarkės klausimuose, problemose, institucijose, pareigybėse,
formose, rezervacijose ir pagalbos užklausose.

Komentaras priskiriamas jį parašiusiam asmeniui ir įrašui, prie kurio jis paliktas. Diskusijose galima
atsakyti į kitų komentarus, paminėti kolegas, rengti greitas apklausas ir pažymėti klausimus išspręstais.

## Kaip tai veikia

- **Prieiga**: komentarų matomumas priklauso nuo įrašo matomumo. Jei turi teisę matyti posėdį,
  problemą ar rezervaciją, gali skaityti to įrašo komentarus ir pats rašyti.
- **Diskusijų gijos**: į pagrindinį komentarą galima atsakyti. Atsakymai sugrupuojami į giją,
  todėl keli atskiri klausimai toje pačioje temoje nesusimaišo.
- **Paminėjimai (`@vardas`)**: komentare įvedus `@`, sistema pasiūlo su šiuo įrašu susijusius asmenis
  (pvz., institucijos narius, posėdžio dalyvius). Paminėtas narys gauna pranešimą pagal savo pranešimų nustatymus.
- **Emocijų reakcijos**: po kiekvienu komentaru galima pridėti reakciją (pvz., nykštį aukštyn, širdelę),
  greitai parodančią pritarimą ar kitą atsaką.
- **Apklausos**: komentare galima sukurti vieno ar kelių atsakymų apklausą, leidžiančią greitai
  surinkti kolegų nuomonę be atskiro posėdžio.
- **Temos išsprendimas**: kai klausimas suderintas, giją galima pažymėti išspręsta.
  Išspręsta gija pažymima žyma **Išspręsta**. Pasirinkus **Rodyti tik neišspręstus**, paslepiamos
  jau užbaigtos temos; **Rodyti visus** grąžina jas į sąrašą.

## Veiksmai

### Komentaro ir atsakymo rašymas

1. Atverk norimo įrašo puslapį (pvz., posėdžio ar problemos).
2. Skiltyje **Diskusija** įrašyk tekstą komentaro laukelyje.
3. Jei nori atkreipti konkretaus asmens dėmesį, įvesk `@` ir pasirink vardą iš sąrašo.
4. Spausk **Komentuoti**.
5. Norėdamas prisidėti prie gijos, po jos pagrindiniu komentaru paspausk **Atsakyti**.

### Apklausos kūrimas komentare

1. Prie komentaro laukelio paspausk **Apklausa**.
2. Atsivėrusiame lange įrašyk klausimą ir atsakymų variantus.
3. Nurodyk, ar leidžiama pasirinkti kelis atsakymus. Jei reikia, nustatyk apklausos pabaigą.
4. Paspausk **Sukurti apklausą**. Ji atsiras diskusijoje, kur kolegos galės balsuoti.

### Redagavimas ir trynimas

- **Redaguoti**: savo komentaro meniu pasirink **Redaguoti**. Pataisyk tekstą ir išsaugok.
  Redaguoti galima **tik savo paties** komentarą.
- **Ištrinti komentarą**:
  - Savo komentarą gali ištrinti pats autorius.
  - Kito asmens komentarą gali ištrinti pagrindinio įrašo redaktorius arba moderatorius, turintis komentarų trynimo teisę.
  - **Kas lieka ištrynus**: ištrinto komentaro tekstas, autorius, reakcijos ir paminėjimai panaikinami visam laikui,
    o jo vietoje gijoje lieka pilka žyma **Komentaras ištrintas**. Taip atsakymai į jį nepraranda konteksto,
    o diskusijos struktūra nesugriūva. Tokio komentaro atkurti negalima.

### Temos pažymėjimas išspręsta

Pagrindinio komentaro meniu **⋯** pasirink **Pažymėti išspręsta**. Gija bus pažymėta kaip išspręsta.
Jei rodai tik neišspręstas temas, ji bus paslėpta. Norėdamas vėl atverti klausimą, prireikus pasirink
**Rodyti visus**, atverk to komentaro meniu ir paspausk **Atžymėti**.

## Kas ką gali {#teises}

| Veiksmas | Bet kuris narys | Galintis redaguoti įrašą | Narys su komentarų trynimo teise |
|---|---|---|---|
| Skaityti ir rašyti komentarus, reaguoti, spręsti temas | ✓, jei mato įrašą | ✓ | ✓, jei mato įrašą |
| Redaguoti savo komentarą | ✓ | ✓ | ✓ |
| Trinti savo komentarą | ✓ | ✓ | ✓ |
| Trinti kito asmens komentarą | – | ✓ | ✓, savo padalinyje arba visur |

Komentarų rašymui atskirų teisių ar rolių nereikia: tai yra [bazinė nario prieiga](/pagrindai/teises#bazine-prieiga),
kuri seka paties įrašo matomumą. Kito žmogaus komentaro redaguoti negali niekas.

Prie įrašų, kurie nepriklauso padaliniui (pagalbos užklausų), vien padalinio
komentarų trynimo teisės nepakanka. Savo komentarą gali trinti autorius, kitų komentarus – įrašą
redaguoti galintis narys arba visos platformos komentarų moderatorius.

## Pranešimai ir automatizavimas {#pranesimai}

- **Paminėjimai**: paminėtas narys numatytai gauna laišką iškart ir pranešimą į įrenginį, jei jame suteikė leidimą.
  Šias parinktis galima pakeisti [pranešimų nustatymuose](/pagrindai/pranesimai).
- **Veikla temoje**: parašius komentarą ar atsakymą gijoje, apie kitų narių atsakymus toje pačioje temoje
  numatytai siunčiamas pranešimas el. pašto suvestinėje. Galima pakeisti siuntimo parinktis arba nutildyti temą.
- **Realaus laiko atnaujinimai**: diskusijų skydelis palaiko tiesioginį ryšį – nauji komentarai, atsakymai,
  reakcijos ir apklausų balsai ekrane atsinaujina iš karto, be puslapio perkrovimo.

## Techninė informacija {#technine-informacija}

- Komentavimo galimybę palaiko šie modeliai (`Commentables::TYPES`): `Meeting`, `AgendaItem`, `Institution`, `Duty`, `Form`, `Problem`, `Reservation`, `SupportRequest`.
- API užklausas apdoroja `CommentApiController`, reakcijas – `CommentReactionApiController`, apklausų balsus – `CommentPollVoteApiController`.
- Prieigos taisykles nustato `CommentPolicy`: `view`, `resolve` ir `react` tikrina pagrindinio įrašo `view` teisę; `update` leidžiama tik autoriui; `delete` leidžiama autoriui, pagrindinio įrašo redaktoriui arba turintiems `comments.delete.padalinys` / `comments.delete.*`.
- Paminėjimams taikomas `CommentMention`, veiklai temoje – `CommentActivity`.
- Komentarų HTML valo `HtmlSanitizerService::sanitizeCommentBody()`.
- Saugų trynimą be gijos pažeidimo atlieka `Comment::erase()`, užpildantis `erased_at` ir išvalantis asmens duomenis.
- Tiesioginį atsinaujinimą užtikrina WebSocket transliavimo kanalas `comments.{type}.{id}` (`CommentBroadcast`).
- Testai: `CommentAuthorizationTest`, `CommentApiTest`, `CommentModelTest`, `PollVoteApiTest`, `CommentNotificationTest`, `DiscussionPanel.component.test.ts`.
