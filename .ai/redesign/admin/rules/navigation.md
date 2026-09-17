# Rules — navigation, workspaces, creating

> **Admin redesign.** Read when: you are touching the shell, the catalog, the palette, or where something lives.
> Index: [README.md](../README.md) · Decisions: [../decisions.md](../decisions.md)

## The model

### Workspaces (draft — validated by the Phase 0 inventory)

A section is visible on `viewAny`; its create action on `create`. `?` = placement to confirm.

| Workspace | Section | Route | Visible when |
|---|---|---|---|
| **Pradžia** | Apžvalga | `dashboard` | always |
| | Užduotys | `userTasks` | always |
| | Pranešimai | `notifications.index` | always |
| **ViSAK** | Apžvalga | `dashboard.atstovavimas` | viewAny Meeting |
| | Institucijos | `institutions.index` | viewAny Institution |
| | Posėdžiai | `meetings.index` | viewAny Meeting |
| | Darbotvarkės klausimai | search `agenda-items` | viewAny Meeting |
| | Problemos | `problems.index` | viewAny Problem |
| | Pareigybių laikotarpiai | `dutiables.timeline` | viewAny Duty |
| | Užduočių suvestinė | `tasks.summary` | ? |
| | Institucijų grafas | `institutionGraph` | viewAny Institution |
| **Rezervacijos** | Apžvalga | `dashboard.reservations` | always |
| | Rezervacijos | `reservations.index` | viewAny Reservation |
| | Ištekliai | `resources.index` | viewAny Resource |
| | Kategorijos | `resourceCategories.index` | viewAny Resource |
| **Svetainė** | Apžvalga (analytics) | `dashboard.svetaine` | viewAny Page |
| | Puslapiai · Naujienos · Kalendorius | `pages` · `news` · `calendar` `.index` | viewAny each |
| | Baneriai · Navigacija · Greitosios nuorodos | `banners` · `navigation` · `quickLinks` `.index` | viewAny each |
| | Renginių tipai · Žymos | `eventTypes` · `tags` `.index` | viewAny each |
| | Failai · Dokumentai | `files.index` · `documents.index` | viewAny each |
| | Studijų rinkiniai ? | `studySets.index` | viewAny StudySet |
| **Organizacija** | Apžvalga (new) | — | any section below |
| | Nariai · Pareigybės | `users.index` · `duties.index` | viewAny each |
| | Pareigybių atnaujinimas (tool) | `duties.updateUsersWizard` | create Duty |
| | Padaliniai · Studijų programos | `tenants.index` · `studyPrograms.index` | viewAny each |
| | Registracijos · Formos | `forms.show` (shared ids) · `forms.index` | policy / viewAny Form |
| **Sistema** | Rolės · Leidimai · Tipai · Ryšiai | `roles` · `permissions` · `types` · `relationships` `.index` | viewAny each |
| | Nustatymai | `settings.index` | `manage-settings` |
| | Sistemos būsena · Laiškų eilė | `systemStatus` · `mailQueue` | ? |
| | Pagalbos užklausos | `supportRequests.index` | viewAny SupportRequest |
| | Sharepoint failai ? | `sharepointFiles.index` | viewAny SharepointFile |

`/mano/administration` survives as **Visi skyriai** — a map of every section the user may open,
generated from the catalog (107 users rely on it).

### Layers

```
Desktop / tablet (≥ md)                                      ← O25: one picker instead of a tab row
MANO VU SA   [ ViSAK ▾ ]     [⌕ Ieškoti ar pereiti…  ⌘K]  [+ Sukurti]  🔔  JK
──────────────────────────────────────────────────────────────────────────────────────────
Apžvalga   Institucijos   Posėdžiai   Problemos   Laikotarpiai

The picker panel (click or hover; the same content is the mobile Meniu):
┌───────────────────────────────────────────────────────────────────────────┐
│ ▣ PRADŽIA          Tavo darbai, užduotys ir pranešimai                    │
│ ▣ ViSAK         ✓  Posėdžiai, institucijos, darbotvarkės      3 laukia    │
│   └ Apžvalga · Institucijos · Posėdžiai · Problemos · Laikotarpiai        │
│ ▣ REZERVACIJOS     Patalpos, įranga, reklama                              │
│ ▣ SVETAINĖ         Puslapiai, naujienos, renginiai                        │
└───────────────────────────────────────────────────────────────────────────┘

Mobile (< md)
MANO VU SA                                        ⌕   🔔
                         …
[ Pradžia ]  [ <primary workspace> ]  [ + ]  [ Užduotys ]  [ Meniu ]
```

1. **Top bar** — wordmark · workspace picker (O25) · field-shaped palette trigger · **+ Sukurti** ·
   bell · account.
2. **Section tabs** — the current workspace's sections, horizontally scrollable with a mask fade.
3. **Breadcrumbs** — only *below* section level (record → sub-record).
4. **Mobile bottom bar** — stable per user; the primary workspace is the one they hold the most
   sections in (ties → ViSAK).
5. **Meniu** (mobile) — full-screen accordion of workspaces → sections (the public mobile nav pattern).
6. **Account menu** — Paskyra, Išvaizda (theme, language, text size), Pagalba, START FM, Apie, Atsijungti.

---

### Navigation and discoverability

1. Every destination and create action is registered **once**, in the catalog.
2. Visibility is `viewAny`; create is `create`. Hidden, never disabled.
3. Nothing is reachable **only** through the palette, a tour, or a hover.
4. URLs don't change; the workspace is derived from the route through the catalog.
5. Every page a notification or email links to makes the next action obvious without navigating.

### Command palette

1. Contains **go to** (catalog sections, workspace as secondary label), **create**, **find**
   (Typesense records), **Neseniai** (empty state), pin stars.
2. One search entry: desktop field-shaped trigger, mobile icon → full screen. "Ieškoti visur" hands
   off to the full-page cross-entity search.
3. Inside a workspace, that workspace's sections and records rank first.

### Creating

- **+ Sukurti** → ActionWindow (permission-filtered, already built).
- Pradžia may surface the top 2–4 permitted actions as shortcuts into the same flows.
- A collection's "Naujas X" opens the entity form, or a sheet for small entities (O7).
