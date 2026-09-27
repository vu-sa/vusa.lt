---
title: Laiškų eilė
area: mailQueue
tests:
  - tests/Feature/Notifications/MailQueueControllerTest.php
  - tests/Browser/MailQueueLayoutTest.php
  - resources/js/Pages/Admin/__tests__/MailQueue.component.test.ts
last_reviewed: 2026-09-27
---

# Laiškų eilė

## Kaip tai veikia

Čia matai dar neišsiųstų pranešimų santraukų gavėjus. Viena eilutė yra vienas pranešimas; kelios
to paties žmogaus eilutės išsiunčiamos vienu santraukos laišku. Skiltis pasiekiama adresu
`/mano/mail-queue`.

## Veiksmai

- Gavėjo eilutėje matai jo vardą, adresą, seniausio pranešimo laiką ir laukiančių eilučių skaičių.
  Pasirink **Peržiūrėti laiško eilutes**, kad pamatytum jų turinį.
- **Atnaujinti** iš naujo įkelia sąrašą.
- **Nesiųsti** prie vienos eilutės arba gavėjo, taip pat **Išvalyti eilę** viršuje, pašalina dar
  neišsiųstus pranešimus. Prieš kiekvieną pašalinimą reikia patvirtinti; atšaukti jo negalima.

## Kas ką gali

Sistemos būsenos ir rolių peržiūros prieigą turintys nariai gali matyti eilę. Tik rolę
„Super Admin“ turintis narys gali pašalinti laukiančius pranešimus.

## Pranešimai ir automatika

Santraukos išsiuntimo laikas priklauso nuo gavėjo pranešimų nustatymų. Pašalintos eilutės į
santrauką nebepatenka.

## Susitarimai

Ši skiltis rodo pranešimų santraukos eilę, ne visas serverio užduotis. Ją išvalyk tik tada, kai
laukiančių pranešimų siųsti nebereikia.

## Techninė informacija

Peržiūra tikrinama per `RolePolicy::viewAny`, o pašalinimo veiksmus papildomai leidžia tik
`Super Admin`. Eilutės saugomos `notification_digest_queue` lentelėje.
