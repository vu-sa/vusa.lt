---
doc_status: reviewed
title: Laiškų eilė
area: mailQueue
models: [NotificationDigestQueue]
last_reviewed: 2026-09-30
tests:
  - tests/Feature/Notifications/MailQueueControllerTest.php
  - tests/Browser/MailQueueLayoutTest.php
  - resources/js/Pages/Admin/__tests__/MailQueue.component.test.ts
---

# Laiškų eilė

## Kaip tai veikia

Čia matai dar neišsiųstų pranešimų santraukų gavėjus. Sąrašo eilutė rodo vieną gavėją;
atvėręs ją matai atskirus laukiančius pranešimus. To paties žmogaus pranešimai išsiunčiami
vienu santraukos laišku. Skiltis pasiekiama adresu
`/mano/mail-queue`.

<DocScreenshot name="mail-queue" alt="Laiškų eilė: gavėjas, jo adresas, seniausio pranešimo laikas ir laukiančių eilučių skaičius" caption="Vienas gavėjas su laukiančiu pranešimu." />

## Veiksmai

- Gavėjo eilutėje matai jo vardą, adresą, seniausio pranešimo laiką ir laukiančių eilučių skaičių.
  Pasirink **Peržiūrėti laiško eilutes**, kad pamatytum jų turinį.
- **Atnaujinti** iš naujo įkelia sąrašą.
- **Nesiųsti** prie vienos eilutės arba gavėjo, taip pat **Išvalyti eilę** viršuje, pašalina dar
  neišsiųstus pranešimus. Prieš kiekvieną pašalinimą reikia patvirtinti; atšaukti jo negalima.

## Kas ką gali {#teises}

Sistemos būsenos ir rolių peržiūros prieigą turintys nariai gali matyti eilę. Tik rolę
„Super Admin“ turintis narys gali pašalinti laukiančius pranešimus.

## Pranešimai ir automatizavimas {#pranesimai}

Santraukos išsiuntimo laikas priklauso nuo gavėjo pranešimų nustatymų. Pašalintos eilutės į
santrauką nebepatenka.

## Techninė informacija {#technine-informacija}

Peržiūra tikrinama per `RolePolicy::viewAny`, o pašalinimo veiksmus papildomai leidžia tik
`Super Admin`. Eilutės saugomos `notification_digest_queue` lentelėje.
