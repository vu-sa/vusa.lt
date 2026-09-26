---
title: Mano
area: dashboard
last_reviewed: 2026-09-27
tests:
  - resources/js/Pages/Admin/__tests__/ShowAdminHome.component.test.ts
  - tests/Browser/AdminHomeLayoutTest.php
---

# Mano

Darbo sritis **Mano** – pirmas puslapis po prisijungimo (`/mano`). Ji atsako į klausimą *kas laukia
manęs?*: tau priskirtos užduotys, artimiausi tavo posėdžiai, tavo rezervacijos ir nauji pranešimai.
Sritis rodoma **visiems** naudotojams.

| Skiltis | Adresas | Kas mato |
|---|---|---|
| Apžvalga | `/mano` | Visi |
| Užduotys | `/mano/tasks` | Visi – tik savo užduotis |
| Pranešimai | `/mano/notifications` | Visi – tik savo pranešimus |

<DocScreenshot name="admin-home" alt="Mano VU SA pradžia: tavo užduotys, greiti veiksmai ir artimiausi posėdžiai" caption="Pradžia – užduotys ir artimiausi tavo posėdžiai vienoje vietoje." href="/mano" />

Pradžios puslapio turinys priklauso nuo tavo pareigybių: studentų atstovas mato savo institucijų
posėdžius ir jų užduotis, išteklių valdytojas – tvirtinimo laukiančias rezervacijas.

## Techninė informacija {#technine-informacija}

- Pradžios puslapis – `ShowAdminHome.vue`; skyriai, kurių tau nereikia (pvz., institucijos, kai jų
  neturi), nerodomi, o likę užima jų vietą.
- Kad puslapis telpa telefone, planšetėje ir kompiuteryje (390, 820, 1180 ir 1440 px pločio ekranuose,
  šviesia ir tamsia tema), tikrina `AdminHomeLayoutTest`.
