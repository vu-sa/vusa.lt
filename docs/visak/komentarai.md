---
doc_status: reviewed
title: Komentarai
last_reviewed: 2026-11-11
tests:
  - tests/Feature/Admin/Discussions/CommentAuthorizationTest.php
  - tests/Feature/Admin/Discussions/CommentApiTest.php
  - tests/Feature/Admin/Discussions/CommentModelTest.php
  - tests/Feature/Admin/Discussions/PollVoteApiTest.php
  - tests/Feature/Notifications/CommentNotificationTest.php
  - tests/Feature/Notifications/MailTemplateRenderTest.php
  - tests/Unit/Notifications/CommentPostedNotificationTest.php
  - resources/js/Components/Discussions/__tests__/DiscussionPanel.component.test.ts
  - resources/js/Features/Admin/ActivityLogViewer/__tests__/RecordActivity.component.test.ts
  - resources/js/Components/Discussions/__tests__/CommentComposer.component.test.ts
  - resources/js/Components/Discussions/__tests__/PollBlock.component.test.ts
  - resources/js/Components/TipTap/__tests__/commentExtensions.test.ts
  - tests/Unit/Services/HtmlSanitizerServiceTest.php
  - tests/Browser/CommentFormattingTest.php
---

# Komentarai

Komentaruose galima aptarti įrašą su kolegomis, užduoti klausimą ar suderinti kitą veiksmą.
Komentarų skydelis rodomas posėdžiuose, darbotvarkės klausimuose, problemose, institucijose, pareigybėse,
formose, rezervacijose, pagalbos užklausose ir Žinių bazės medžiagoje (ten jis vadinamas **Įžvalgos**).

Komentaras priskiriamas jį parašiusiam asmeniui, jo pareigoms ir įrašui, prie kurio jis paliktas. Diskusijose galima
atsakyti į kitų komentarus, paminėti kolegas, pridėti nuotraukų, pradėti atskiras temas ir rengti greitas apklausas.

## Kaip tai veikia

- **Prieiga**: komentarų matomumas priklauso nuo įrašo matomumo. Jei turi teisę matyti posėdį,
  problemą ar rezervaciją, gali skaityti to įrašo komentarus ir pats rašyti.
- **Diskusijų gijos**: į pagrindinį komentarą galima atsakyti. Atsakymai sugrupuojami į giją,
  todėl keli atskiri klausimai toje pačioje temoje nesusimaišo. Kai atsakymų daug, rodomi du naujausi,
  o ankstesni atveriami mygtuku **Rodyti ankstesnius atsakymus**.
- **Temos**: didesniam klausimui galima pradėti temą – su pavadinimu ir atskira atsakymų gija.
  Temos rodomos virš komentarų suskleistos: matyti pavadinimas, autorius, atsakymų skaičius ir paskutinio
  atsakymo laikas; paspaudus tema atsiveria.
- **Pareigos prie vardo**: prie kiekvieno komentaro rodomos autoriaus pareigos ir padalinys (visas institucijos pavadinimas matomas pareigų sąraše), kad būtų aišku,
  kas kalba (pvz., koordinatorius ar atstovas). Pareigos išsaugomos su komentaru, todėl seni komentarai rodo
  tas pareigas, kurias autorius ėjo rašydamas. Jei komentaras parašytas iš jau pasibaigusių pareigų, prie jų rodomas laikotarpis.
- **Nuotraukos**: prie komentaro galima pridėti iki keturių nuotraukų; paspaudus jos atveriamos visu dydžiu.
- **Paminėjimai (`@vardas`)**: komentare įvedus `@`, sistema pasiūlo su šiuo įrašu susijusius asmenis
  (pvz., institucijos narius, posėdžio dalyvius). Paminėtas narys gauna pranešimą pagal savo pranešimų nustatymus.
- **Reakcijos**: kiekvieno komentaro kampe yra mygtukai **Patinka**, **Pridėti reakciją**, **Atsakyti** ir meniu **⋯**
  (telefone jie rodomi po komentaru). Užvedus pelę ant **Pridėti reakciją**, atsiveria kitos reakcijos:
  širdelė, sutinku, sveikinu, peržiūrėsiu, ačiū. Pasirinktos reakcijos rodomos po tekstu su skaičiumi; užvedus ant
  jos matyti, kas reagavo, o paspaudus ji pridedama arba atšaukiama. Paspaudus **Atsakyti** dar kartą, atsakymo laukelis uždaromas.
- **Apklausos**: komentare galima sukurti vieno ar kelių atsakymų apklausą, leidžiančią greitai
  surinkti kolegų nuomonę be atskiro posėdžio.
- **Temos užbaigimas**: kai klausimas aptartas, temą galima užbaigti. Užbaigta tema pažymima žyma
  **Užbaigta**, į ją nebeatsakoma, o sąraše ji paslepiama po mygtuku **Rodyti užbaigtas temas**.
  Paprastų komentarų užbaigti nereikia.
- **Skydelis prisitaiko prie įrašo**: Mano kelio žingsniuose pirmiausia rodomos temos, pagalbos
  užklausose rodomas paprastas susirašinėjimas be temų ir apklausų, kituose įrašuose – komentarai su temomis ir apklausomis.

<ChangelogNote date="2026-11-11" title="Temos, pareigos ir nuotraukos komentaruose">

Rašymo laukelis ramybės būsenoje užima vieną eilutę, formatavimas paslėptas po mygtuku. Atsirado temos,
pareigos prie vardo ir nuotraukos, o apklausa kuriama tiesiai laukelyje. Žymėjimas „Išspręsta“ pakeistas temų užbaigimu.

</ChangelogNote>

## Veiksmai

### Komentaro ir atsakymo rašymas

1. Atverk norimo įrašo puslapį (pvz., posėdžio ar problemos).
2. Skiltyje **Komentarai** (pagalbos užklausose – **Diskusija**, Žinių bazės medžiagoje – **Įžvalgos**) paspausk
   komentaro laukelį. Jis išsiskleidžia: viršuje gali pasirinkti **Komentaras** (numatyta), **Tema** arba **Apklausa**.
3. Įrašyk tekstą. Jei nori atkreipti konkretaus asmens dėmesį, įvesk `@` arba spausk **@** mygtuką apačioje ir pasirink vardą iš sąrašo.
   Apatinėje laukelio juostoje matysi, kas gaus pranešimą apie paminėjimą (redaguojant komentarą pranešimai iš naujo nesiunčiami).
4. Nuotrauką pridėk paveikslėlio mygtuku arba įklijuok ją į laukelį.
5. Jei turi kelias pareigas, laukelio viršuje esančiame **Kaip …** sąraše pasirink, kurios pareigos bus rodomos prie tavo vardo.
   Sąraše yra ir ankstesnės pareigos su jų laikotarpiu – pvz., jei nori pasidalyti patirtimi iš buvusių pareigų.
   Jei nieko nekeiti, siūlomos pareigos parenkamos taip:
   1. pareigos, susijusios su komentuojamu įrašu (ta pati institucija, jos posėdis ar pati pareigybė);
   2. kitu atveju – paskutinį kartą tavo pasirinktos pareigos;
   3. kitu atveju – koordinatoriaus pareigos;
   4. kitu atveju – pareigos svarbiausioje institucijoje (VU SA, Centrinis biuras, VU SA valdymo organai,
      padalinių ir PKP institucijos), o esant lygiai – naujausios.

   Ta pačia tvarka pareigos išdėstytos ir sąraše; ankstesnės pareigos rodomos pabaigoje ir pačios nesiūlomos.
6. Spausk **Komentuoti**. **Ctrl/Cmd + Enter** taip pat išsiunčia komentarą.
7. Norėdamas prisidėti prie gijos, po jos pagrindiniu komentaru paspausk **Atsakyti**. Parašęs tekstą,
   spausk **Atsakyti** rašymo laukelyje – atsakymas atsiras toje pačioje gijoje. Tai veikia visose
   aukščiau išvardytose įrašų skiltyse. Gijos atsakymai rodomi viename lygyje ir išlieka iš naujo atvėrus įrašą.

### Teksto formatavimas {#formatavimas}

Komentaruose, atsakymuose ir apklausos klausime galima paryškinti tekstą, rašyti kursyvu,
pabraukti, perbraukti, pridėti nuorodą ir kurti ženklelių arba numeruotus sąrašus su įtraukomis.
Formatavimas išlieka išsaugojus ir vėl atvėrus komentarą.

1. Apatinėje laukelio juostoje paspausk **Formatavimas** (raidės T mygtukas) – virš teksto atsiras formatavimo juosta.
   Pažymėk tekstą ir pasirink reikiamą mygtuką.
2. Sąrašui pasirink ženklelių arba numeruotą sąrašą. Jį galima pradėti ir įvedus `- ` arba `1. `.
3. Meniu **Daugiau parinkčių** rasi pabraukimą, perbraukimą, sąrašo įtraukas,
   veiksmų atšaukimą, pakartojimą ir formatavimo išvalymą.
4. Nuorodai pasirink grandinėlės mygtuką arba paspausk **Ctrl/Cmd + K** (juosta atsiveria pati). Įrašyk adresą;
   nuorodą taip pat galima pakeisti ar pašalinti.

**Enter** tęsia pastraipą ar sąrašą, **Shift + Enter** įterpia eilutės lūžį.
Lentelės, antraštės ir failų priedai komentaruose nesiūlomi; nuotraukas galima pridėti.

<ChangelogNote date="2026-10-26" title="Komentarų formatavimas">

Sąrašų ženkleliai ir numeracija matomi tiek rašant, tiek išsaugojus komentarą.
Formatavimo mygtukai veikia ir telefone.

</ChangelogNote>

### Temos pradėjimas {#temos}

1. Paspausk komentaro laukelį ir viršuje pasirink **Tema**.
2. Įrašyk temos pavadinimą ir pirmą žinutę.
3. Paspausk **Pradėti temą**. Tema atsiras sąraše **Temos** ir liks atverta.
4. Atsakyti į temą galima mygtuku **Atsakyti** atvertos temos apačioje.

Temos autorius arba įrašą redaguoti galintis narys atvertos temos apačioje gali paspausti **Užbaigti temą**.
Užbaigtą temą galima vėl atverti mygtuku **Atnaujinti temą**.

### Apklausos kūrimas komentare

1. Paspausk komentaro laukelį ir viršuje pasirink **Apklausa**.
2. Laukelyje įrašyk klausimą, o po juo – atsakymų variantus. **Taip / Ne** užpildo du variantus iš karto,
   **Enter** paskutiniame variante prideda naują.
3. Jei reikia, **Nustatymai** leidžia rinktis kelis atsakymus ir kalendoriuje su laiku nurodyti, kada apklausa baigiasi.
4. Paspausk **Paskelbti apklausą**. Ji atsiras diskusijoje, kur kolegos galės balsuoti.
   Prie variantų rodomi balsavusiųjų veidai, o visą sąrašą atveria **Kas balsavo**.

### Redagavimas ir trynimas

- **Redaguoti**: savo komentaro meniu pasirink **Redaguoti**. Pataisyk tekstą ir išsaugok.
  Redaguoti galima **tik savo paties** komentarą ir tik **per 15 minučių** nuo jo parašymo; ištrinti galima bet kada.
- **Ištrinti komentarą**:
  - Savo komentarą gali ištrinti pats autorius.
  - Kito asmens komentarą gali ištrinti pagrindinio įrašo redaktorius arba moderatorius, turintis komentarų trynimo teisę.
  - **Kas lieka ištrynus**: ištrinto komentaro tekstas, autorius, reakcijos ir paminėjimai panaikinami visam laikui,
    o jo vietoje gijoje lieka pilka žyma **Komentaras ištrintas**. Taip atsakymai į jį nepraranda konteksto,
    o diskusijos struktūra nesugriūva. Tokio komentaro atkurti negalima.

## Kas ką gali {#teises}

| Veiksmas | Bet kuris narys | Galintis redaguoti įrašą | Narys su komentarų trynimo teise |
|---|---|---|---|
| Skaityti ir rašyti komentarus, pradėti temas, reaguoti, balsuoti | ✓, jei mato įrašą | ✓ | ✓, jei mato įrašą |
| Užbaigti ir atnaujinti temą | Tik savo temą | ✓ | ✓, savo padalinyje arba visur |
| Redaguoti savo komentarą (per 15 min.) | ✓ | ✓ | ✓ |
| Trinti savo komentarą | ✓ | ✓ | ✓ |
| Trinti kito asmens komentarą | – | ✓ | ✓, savo padalinyje arba visur |

Komentarų rašymui atskirų teisių ar rolių nereikia: tai yra [bazinė nario prieiga](/pagrindai/teises#bazine-prieiga),
kuri seka paties įrašo matomumą. Kito žmogaus komentaro redaguoti negali niekas.

Prie įrašų, kurie nepriklauso padaliniui (pagalbos užklausų), vien padalinio
komentarų trynimo teisės nepakanka. Savo komentarą gali trinti autorius, kitų komentarus – įrašą
redaguoti galintis narys arba visos platformos komentarų moderatorius.

## Pranešimai ir automatizavimas {#pranesimai}

Laiško antraštė nurodo aptariamą įrašą (apklausai – „Nauja apklausa: …“), o viena eilutė – kas paliko komentarą, paminėjo narį ar pradėjo apklausą.
Po ja rodoma trumpa komentaro citata be formatavimo; temos ir atsakymo į temą citata prasideda temos pavadinimu. Citata pateikiama ir el. pašto suvestinėje bei
pranešimų varpelyje, o pranešime į įrenginį rodoma trumpesnė ištrauka. Ilgą komentarą galima perskaityti atvėrus įrašą.

- **Paminėjimai**: paminėtas narys numatytai gauna laišką iškart ir pranešimą į įrenginį, jei jame suteikė leidimą.
  Šias parinktis galima pakeisti [pranešimų nustatymuose](/pagrindai/pranesimai).
- **Veikla temoje**: parašius komentarą ar atsakymą gijoje, apie kitų narių atsakymus toje pačioje temoje
  numatytai siunčiamas pranešimas el. pašto suvestinėje. Galima pakeisti siuntimo parinktis arba nutildyti temą.
- **Realaus laiko atnaujinimai**: diskusijų skydelis palaiko tiesioginį ryšį – nauji komentarai, atsakymai,
  reakcijos ir apklausų balsai ekrane atsinaujina iš karto, be puslapio perkrovimo.

## Techninė informacija {#technine-informacija}

- Komentavimo galimybę palaiko šie modeliai (`Commentables::TYPES`): `KnowledgeItem`, `Meeting`, `AgendaItem`, `Institution`, `Duty`, `Form`, `Problem`, `Reservation`, `SupportRequest`. Skydelio išdėstymą ir leidžiamas temas bei apklausas kiekvienam tipui nustato `Commentables::CAPABILITIES`.
- Temos yra `CommentKind::Topic` komentarai su `title`; užbaigimas naudoja `resolved_at`/`resolved_by`. Autoriaus pareigos saugomos `author_dutiable_id` (`ResolveCommentAuthorDuty`), pasirinkimas įsimenamas `ui_preferences.comment_dutiable_id`. Nuotraukos – `Comment::IMAGES` medijos kolekcija, prisegama iš laikinų įkėlimų.
- API užklausas apdoroja `CommentApiController`, reakcijas – `CommentReactionApiController`, apklausų balsus – `CommentPollVoteApiController`.
- Prieigos taisykles nustato `CommentPolicy`: `view` ir `react` tikrina pagrindinio įrašo `view` teisę; `resolve` leidžiamas tik temai, jos autoriui arba moderatoriui; `update` leidžiama tik autoriui; `delete` leidžiama autoriui, pagrindinio įrašo redaktoriui arba turintiems `comments.delete.padalinys` / `comments.delete.*`.
- Paminėjimams taikomas `CommentMention`, veiklai temoje – `CommentActivity`.
- Komentarų HTML valo `HtmlSanitizerService::sanitizeCommentBody()`.
- Saugų trynimą be gijos pažeidimo atlieka `Comment::erase()`, užpildantis `erased_at` ir išvalantis asmens duomenis.
- Tiesioginį atsinaujinimą užtikrina WebSocket transliavimo kanalas `comments.{type}.{id}` (`CommentBroadcast`).
- Testai: `CommentAuthorizationTest`, `CommentApiTest`, `CommentModelTest`, `PollVoteApiTest`, `CommentNotificationTest`, `DiscussionPanel.component.test.ts`.
