---
doc_status: reviewed
title: Pranešimai
coverage: ignore
last_reviewed: 2026-10-25
tests:
  - tests/Feature/Notifications/NotificationFiringTest.php
  - tests/Feature/Tasks/Subscribers/MeetingTaskSubscriberTest.php
  - tests/Feature/Notifications/DigestSystemTest.php
  - tests/Feature/Notifications/NotificationSettingsTest.php
  - tests/Feature/Notifications/PushDeliveryTest.php
  - tests/Feature/Notifications/MutingBehaviorTest.php
  - tests/Feature/Notifications/InstitutionActivityAnswerTest.php
  - tests/Feature/Notifications/MailTemplateRenderTest.php
---

# Pranešimai

Platforma praneša apie įvykius, reikalaujančius tavo dėmesio, sprendimo ar atsakymo: naujas užduotis,
artėjančius posėdžius, patvirtinimo laukiančias rezervacijas, komentarus ir paminėjimus.

Pranešimų nustatymuose galima pasirinkti, apie ką ir kokiu būdu norima būti informuojamam.

Rezervacijų peržiūrų gavėjus nustato **Rezervacijų administravimo** paskirtis. Pasikeitus
atsakingiems žmonėms, nebaigtų peržiūrų priminimai tenka dabartiniams nariams. Rezervacijos
dalyviai atskirai gauna būsenų pasikeitimus. Žr. [Kontaktai ir istorija](/rezervacijos/rezervacijos#kontaktai-ir-istorija).

## Pranešimų kanalai {#kanalai}

<ChangelogNote date="2026-10-02" title="Laiško pristatymą rinkis kiekvienai pranešimų rūšiai">

Laiškas iškart, suvestinė ir išjungimas yra atskiri pasirinkimai. Įrenginio pranešimai valdomi atskirai, o nutildymas nepašalina pranešimo iš varpelio. Ramybės valandos neatideda pavienių laiškų.

</ChangelogNote>

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

Laiškai pradedami pasisveikinimu vardu; lietuviškai vartojamas šauksmininkas. Bendroje poraštėje trumpai paaiškinta, kas yra Mano VU SA, ir pateikiama nuoroda į platformą.

Pranešimų laiškuose ir suvestinėse veiksmai pateikiami nedideliais neutraliais mygtukais. Ilgesni mygtukų pavadinimai telefone gali užimti kelias eilutes. Laiško pabaigoje yra nuoroda į pranešimų nustatymus.

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

Užduoties pranešimas siunčiamas tik tada, kai užduotį priskiria žmogus. Automatinės užduotys,
pvz., sukurti ar užpildyti posėdžio darbotvarkę, atsiranda [Užduotyse](/mano/uzduotys) be atskiro
pranešimo – apie jas primenama artėjant terminui. Apie naują posėdį atstovas, kuriam tenka
darbotvarkės užduotis, sužino vienu pranešimu **„Sukurtas naujas posėdis“**, kaip ir koordinatoriai.
Posėdį sukūręs narys šio pranešimo negauna.

<ChangelogNote date="2026-10-19" title="Automatinės užduotys be atskiro pranešimo">

Anksčiau kiekvienas naujas posėdis atstovo suvestinėje palikdavo dvi „Nauja užduotis“ eilutes, iš kurių viena jau būdavo atlikta.

</ChangelogNote>

Pagal institucijos posėdžių periodiškumą gali gauti **„VU SA · Ar vyko posėdis?“** arba
**„VU SA · Papildyk posėdžių įrašus“**. Automatiškai toks laiškas siunčiamas vieną kartą, kai
institucija pradeda vėluoti. Tą patį gali inicijuoti koordinatorius.
Užklausa nurodo tavo laikotarpį; jo pabaiga užfiksuota siunčiant.

- Veiklos užklausoje galima pridėti posėdžius arba pasirinkti **Ne, nevyko**.
- Įrašų papildymo užklausoje rodomi jau vykę posėdžiai be darbotvarkės arba su neužpildytais
  sprendimais. Juos galima atverti ir prisijungus papildyti. Pasirinkus **Viskas užfiksuota**
  patvirtinama, kad darbotvarkės ir sprendimai yra užpildyti. Jei trūksta paties posėdžio, galima jį pridėti.
- Abiem atvejais galima pasirinkti **Nesu šio organo narys (-ė)**, kad koordinatorius patikrintų duomenis.

Atsakymo nuoroda galioja 14 dienų ir leidžia atsakyti neprisijungus. Vien jos atvėrimas nieko
neįrašo. Vienu atsakymu gali įrašyti kelis posėdžius; sprendimams el. paštu laiko nereikia.
Išsamiau aprašyta skyriuje [apie atsakymą iš laiško](/visak/institucijos#atsakymas).

Šios užklausos laikosi tavo pranešimų nustatymų: **Laiškas iškart**, **Santraukoje** arba
**Nesiųsti**. Nutildymas taip pat taikomas. Pasenę klausimai nesiunčiami, o „įtraukta į eilę“
nereiškia, kad laiškas jau pristatytas. Užklausų istorija pateikiama
[institucijos puslapyje](/visak/institucijos#uzklausos).

**Suvestinėje** kiekviena rodoma institucija turi savo laikotarpį ir pasirašytas atsakymo
nuorodas: veiklos klausime **Taip, vyko** / **Ne, nevyko**, papildymo klausime **Papildyti įrašus** /
**Viskas užfiksuota**, abiem atvejais **Nesu šio organo narys (-ė)**. Papildymo klausime pateikiamos
ir konkrečių neužpildytų posėdžių nuorodos. Jos yra ir tekstinėje laiško versijoje.

Visi laiškai pasirašomi **Mano VU SA** vardu, ne koordinatoriaus.

## Kur rasti pranešimus ir nustatymus {#kur-rasti}

- **Tavo gauti pranešimai**: pasiekiami skiltyje [Mano → Pranešimai](/mano/pranesimai) (`/mano/notifications`).
- **Tavo pranešimų nustatymai**: pasiekiami profilio meniu viršuje dešinėje pasirinkus
  **Pranešimų nustatymai** (`/mano/profile/notifications`). Čia galima keisti kiekvienos skilties el. pašto ir
  pranešimų į įrenginį parinktis, suvestinės dažnumą bei valdyti susietus įrenginius.

<DocScreenshot name="notification-preferences" alt="Pranešimų nustatymai: kiekvienam pranešimui pasirenkamas laiškas iškart, suvestinė arba be laiško ir push, šone laikinas išjungimas ir el. pašto adresai" caption="Pranešimų nustatymai: kiekvienai pranešimų rūšiai – laiškas, suvestinė ar išjungta; šone – nutildymas ir adresai." />

## Techninė informacija {#technine-informacija}

- Pranešimai paveldi `BaseNotification` ir privalo nurodyti `NotificationType` reikšmę.
- Saugojimą duomenų bazėje atlieka `IdempotentDatabaseChannel`, užkertantis kelią pasikartojantiems įrašams.
- El. pašto suvestines kaupia `NotificationDigestQueue`, o išsiunčia konsolės komanda `notifications:send-digests`.
- Kanalus ir nutildymą tikrina `BaseNotification::via()`. Ramybės valandos taikomos pranešimams į įrenginį per `BaseNotification::withDelay()` ir suvestinėms per `ProcessNotificationDigests`.
- El. pašto ir įrenginio numatytosios parinktys nustatomos atskirai: `NotificationType::defaultEmail()` ir `defaultPush()`.
- Periodiškumo užduotis kuria `PeriodicityGapTaskHandler`; kai būklė „Vėluoja“, `tasks:repopulate institution` per `SendInstitutionActivityRequests::forTask()` sukuria `InstitutionActivityRequest` kiekvienam gavėjui ir siunčia `InstitutionActivityNotification` su pasirašytomis atsakymo nuorodomis. Šią elgseną tikrina `InstitutionActivityAnswerTest`.
