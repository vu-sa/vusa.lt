---
doc_status: reviewed
title: Pranešimai
coverage: ignore
last_reviewed: 2026-09-30
tests:
  - tests/Feature/Notifications/NotificationFiringTest.php
  - tests/Feature/Notifications/DigestSystemTest.php
  - tests/Feature/Notifications/NotificationSettingsTest.php
  - tests/Feature/Notifications/PushDeliveryTest.php
  - tests/Feature/Notifications/MutingBehaviorTest.php
  - tests/Feature/Notifications/InstitutionActivityAnswerTest.php
---

# Pranešimai

Platforma praneša apie įvykius, reikalaujančius tavo dėmesio, sprendimo ar atsakymo: naujas užduotis,
artėjančius posėdžius, patvirtinimo laukiančias rezervacijas, komentarus ir paminėjimus.

Pranešimų nustatymuose pasirink, apie ką ir kokiu būdu nori būti informuojamas.

## Pranešimų kanalai {#kanalai}

Pranešimai gali pasiekti tave trimis kanalais:

1. **Platformoje (varpelis)**: matomas viršutinėje juostoje (telefone – apatinėje). Skaičius rodo visus
   neperskaitytus pranešimus, o sąraše matai 20 naujausių; visus rasi paspaudęs **Rodyti visus pranešimus**. Šio kanalo **negalima išjungti ar nutildyti** – net išjungus el. paštą, pranešimus
   vis tiek rasi platformoje, kol jų neištrinsi.
2. **El. paštas**: kiekvienai pranešimų rūšiai gali pasirinkti pristatymo būdą:
   - **Laiškas iškart** – atskiras laiškas išsiunčiamas tuoj pat.
   - **Suvestinėje** – pranešimas įtraukiamas į periodinę neskaitytų pranešimų santrauką (kas 1, 4, 12 arba 24 val.).
   - **Išjungta** – el. laiškai nesiunčiami. Pranešimai platformoje ir įrenginyje valdomi atskirai.
3. **Pranešimai į įrenginį („push“)**: siunčiami tiesiai į tavo naršyklę ar telefoną, jei įrenginyje
   suteikei leidimą.

## Skubumo lygiai ir numatytosios parinktys {#skubumas}

Pranešimo skubumas lemia numatytąjį el. pašto siuntimo būdą. Jį gali pakeisti nustatymuose,
išskyrus žemiau aprašytus registracijų pranešimus. Visi pranešimai taip pat rodomi platformoje:

| Skubumas | Numatytasis el. pašto siuntimas | Kam taikoma | Pavyzdžiai |
|---|---|---|---|
| **Veiksmas** | Laiškas iškart | Reikalauja greito tavo žingsnio ar sprendimo | Užduoties priminimas, vėlavimas, patvirtinimo prašymas, paminėjimas komentare, besibaigiantis pareigybės laikotarpis, klausimas dėl įvykusio posėdžio |
| **Žinoti** | Suvestinė el. paštu | Reikšminga informacija, nereikalaujanti skubaus veiksmo | Naujai paskirta užduotis, naujas posėdis, užpildyta posėdžio darbotvarkė, diskusija komentuotoje temoje |
| **Įrašas** | Nesiunčiama | Sistemos užfiksuotas faktas | Automatiškai sistemos užbaigta užduotis |
| **Įvadas** | Nesiunčiama | Pirmieji sistemos žingsniai | Sveikinimo pranešimas |

Pranešimai į įrenginį nustatomi atskirai. Jie numatytai įjungti užduočių ir posėdžių priminimams,
patvirtinimo prašymams, paminėjimams komentaruose ir sekamų institucijų veiklai. Kad juos gautum,
įrenginyje reikia suteikti leidimą.

::: tip Registracijų laiškų išjungti negalima
Pranešimai apie naujas narių ir studentų atstovų registracijas siunčiami tiesiai į pareigybės institucinę
pašto dėžutę. Kad organizacija nepraleistų naujų narių, šių pranešimų el. pašto siuntimo išjungti negalima.
:::

## Ramybės valandos ir nutildymas {#ramybe-ir-nutildymas}

- **Ramybės valandos (22:00–07:00)**: nakties metu pranešimai į telefoną („push“) ir el. pašto
  suvestinės laukia ryto. Pavieniai el. laiškai šiomis valandomis neatidedami, jei pranešimai nėra nutildyti.
- **Nutildymas**: jei nori susikaupti, profilio nustatymuose gali laikinai nutildyti pranešimus
  (1 valandai, 4 valandoms arba iki rytojaus). Nutildymo metu nesiunčiami pavieniai el. laiškai, pranešimai į įrenginį ir suvestinės,
  išskyrus privalomus registracijų laiškus. Varpelyje pranešimai kaupiasi įprastai.

## Kas ir kada gauna pranešimus {#auditorija}

Gavėjai priklauso nuo įvykio: užduoties pranešimą gauna jos vykdytojas, rezervacijos pranešimą –
teikėjas ar tvirtintojas, paminėjimą – komentare pasirinktas narys. Institucijos pranešimų gavėjai
parenkami pagal jų pareigas ar institucijos sekimą. Todėl galiojančios pareigos nėra būtina sąlyga
visų rūšių pranešimams gauti.

Kai atstovui paskiriama darbotvarkės rengimo užduotis, jis gauna užduoties pranešimą ir negauna
papildomo bendro pranešimo apie tą patį naują posėdį.

Jei institucija kurį laiką neužfiksavo posėdžio ar pranešimo, kad jo nebuvo, pagal jos posėdžių
periodiškumą siunčiamas priminimas **„Ar vyko posėdis?“**. Jame gali pasirinkti:

- **Taip, fiksuoti** – atverti posėdžio registravimo langą;
- **Ne, nevyko** – pranešti, kad posėdžio nebuvo.

Užfiksavus posėdį ar pranešus, kad jo nebuvo, susijusi priminimo užduotis užbaigiama.
Išsamiau skaityk [apie posėdžių periodiškumą](/visak/institucijos#periodiskumas).

## Kur rasti pranešimus ir nustatymus {#kur-rasti}

- **Tavo gauti pranešimai**: atverk [Mano → Pranešimai](/mano/pranesimai) (`/mano/notifications`).
- **Tavo pranešimų nustatymai**: paspausk savo profilį viršuje dešinėje ir pasirink
  **Pranešimų nustatymai** (`/mano/profile/notifications`). Čia gali keisti kiekvienos skilties el. pašto ir
  pranešimų į įrenginį parinktis, suvestinės dažnumą bei valdyti susietus įrenginius.

## Techninė informacija {#technine-informacija}

- Pranešimai paveldi `BaseNotification` ir privalo nurodyti `NotificationType` reikšmę.
- Saugojimą duomenų bazėje atlieka `IdempotentDatabaseChannel`, užkertantis kelią pasikartojantiems įrašams.
- El. pašto suvestines kaupia `NotificationDigestQueue`, o išsiunčia konsolės komanda `notifications:send-digests`.
- Kanalus ir nutildymą tikrina `BaseNotification::via()`. Ramybės valandos taikomos pranešimams į įrenginį per `BaseNotification::withDelay()` ir suvestinėms per `ProcessNotificationDigests`.
- El. pašto ir įrenginio numatytosios parinktys nustatomos atskirai: `NotificationType::defaultEmail()` ir `defaultPush()`.
- Periodiškumo priminimus generuoja `PeriodicityGapTaskHandler`, o pranešimą `InstitutionActivityNotification` siunčia `HandleTaskCreated` klausytojas. Šią elgseną tikrina `InstitutionActivityAnswerTest`.
