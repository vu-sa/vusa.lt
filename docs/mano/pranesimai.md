---
doc_status: reviewed
title: Pranešimai
area: notifications
models: [DatabaseNotification]
last_reviewed: 2026-10-03
tests:
  - tests/Feature/Notifications/NotificationCountTest.php
  - tests/Feature/Notifications/DigestSystemTest.php
  - tests/Feature/Notifications/NotificationSettingsTest.php
  - resources/js/Pages/Admin/__tests__/ShowNotifications.component.test.ts
  - resources/js/Features/Admin/Notifications/__tests__/NotificationCard.component.test.ts
  - resources/js/Components/__tests__/NotificationsIndicator.component.test.ts
---

# Pranešimai

Skiltyje **Pranešimai** rodomi tau skirti pranešimai. Ją galima pasiekti adresu `/mano/notifications`
arba paspaudus varpelį viršutinėje juostoje (telefone – apatinėje).

Čia pateikiami pranešimai apie naujas ir vėluojančias užduotis, artėjančius posėdžius,
patvirtinimo laukiančias rezervacijas, komentarus ir paminėjimus. Kaip pranešimai siunčiami el. paštu ir
į telefoną, aprašyta [Pranešimų pagrinduose](/pagrindai/pranesimai).

## Rekomendacijos {#rekomendacijos}

::: tip
Pažymėti pranešimą kaip skaitytą ir atlikti jame nurodytą darbą – atskiri veiksmai.
:::

## Kaip tai veikia

Pranešimų puslapyje rodomi tau skirti pranešimai, naujausi – pirmiausia:

- **Pranešimo kortelė**: rodo pranešimo pavadinimą, paaiškinantį tekstą, gavimo laiką ir susijusią
  informaciją (pvz., instituciją, rezervuotą daiktą ar terminą).
- **Tiesioginis veiksmas**: dauguma pranešimų turi pagrindinį veiksmo mygtuką (pvz., *Peržiūrėti posėdį*,
  *Peržiūrėti užduotis*, *Peržiūrėti rezervaciją*), leidžiantį vienu paspaudimu pereiti tiesiai prie darbo.
  Varpelio sąraše ir pranešimų puslapyje jis rodomas piktograma šalia skaitymo žymos. Piktograma
  atitinka veiksmą; užvedus žymeklį rodomas jo pavadinimas.
- **Greitieji filtrai viršuje**:
  - **Neskaityti** (su skaitliuku) – rodo tik naujus, dar neperžiūrėtus pranešimus. Tai numatytasis rodinys.
  - **Visi** – rodo ir skaitytus, ir neskaitytus pranešimus.

## Veiksmai

### Pranešimo atidarymas ir atlikimas

1. Paspausk pranešimo pavadinimą arba jo veiksmo mygtuką.
2. Sistema atvers susijusį įrašą arba veiksmų langą.
3. Atidarytas pranešimas pažymimas kaip skaitytas.

### Skaitymo būsenos valdymas

- **Pažymėti kaip skaitytą**: kortelėje paspausk žymėjimo piktogramą.
- **Pažymėti visus kaip skaitytus**: sąrašo viršuje paspausk **⋯ Veiksmai → Pažymėti visus kaip skaitytus**.
  Visi tavo neskaityti pranešimai bus pažymėti kaip skaityti.

### Pranešimų šalinimas

- **Ištrinti pavienį pranešimą**: kortelėje paspausk trynimo piktogramą.
- **Ištrinti perskaitytus**: per **⋯ Veiksmai** pasirink **Ištrinti perskaitytus**. Visi jau peržiūrėti
  pranešimai bus pašalinti, paliekant tik naujus.
- **Ištrinti visus**: per **⋯ Veiksmai** pasirink **Ištrinti visus** (veiksmas pažymėtas raudonai).
  Visa pranešimų istorija bus išvalyta.

### Nustatymų keitimas

Viršuje esantis mygtukas **Pranešimų nustatymai** nukreipia į tavo profilio pranešimų nustatymus
(`/mano/profile/notifications`), kur galima pasirinkti el. pašto laiškų dažnumą ir įjungti pranešimus į įrenginį.

## Kas ką gali {#teises}

| Veiksmas | Bet kuris narys | Administratorius |
|---|---|---|
| Matyti pranešimus | ✓ Tik savo | ✓ Tik savo |
| Žymėti skaitytais ir trinti | ✓ Tik savo | ✓ Tik savo |
| Matyti kito nario pranešimus | – | – |

Šioje skiltyje kiekvienas narys mato ir valdo **tik savo pranešimus**. Čia ir superadministratorius
mato savo, o ne kitų narių pranešimų dėžutę.

## Pranešimai ir automatizavimas {#automatizavimas}

Pažymėjus pranešimą kaip skaitytą platformoje, jis pašalinamas iš dar neišsiųstos el. pašto
suvestinės eilės. Vien pamatyti pranešimą naršyklėje nepakanka – reikia jį pažymėti kaip skaitytą.
Tai nekeičia jau išsiųstų laiškų.

## Techninė informacija {#technine-informacija}

- Skiltį valdo `UserNotificationsController` (`index`, `markAsRead`, `markAllAsRead`, `destroy`, `destroyAll`).
- Sąsaja naudoja `CollectionPage.vue` kartu su `ShowNotifications.vue` ir `NotificationCard.vue`.
- Pažymėjus skaitytu (`markAsRead` / `markAllAsRead`), atitinkami įrašai ištrinami iš `NotificationDigestQueue` lentelės.
- Prieiga tikrinama pagal sesiją: užklausos filtruojamos pagal prisijungusio naudotojo ID (`Auth::id()`), o pranešimų ID ieškoma tik jo pranešimuose.
- Testai: `NotificationCountTest`, `DigestSystemTest`.
