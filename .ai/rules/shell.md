---
paths:
  - 'app/Services/AdminNavigation/**'
  - 'resources/js/Components/Layouts/Shell/**'
---

# Shell

## Admin navigation is one server-side catalog
- `AdminNavigationCatalog` registers every workspace, section and create action once. The shell tabs, workspace picker, mobile Meniu, command palette, + Sukurti (ActionWindow) and Visi skyriai all read it; never hand-write a second menu list.
- A section shows on `viewAny`, a create action on `create`. Hidden, never disabled.
- Nothing is reachable only through the palette, a tour or a hover.
- URLs don't change; the workspace is derived from the route via the catalog. A new admin index route must be registered, or deliberately excluded in the route-coverage test (`AdminNavigationCatalogTest`).
- The seven workspaces are Pradžia, Wiki, ViSAK, Rezervacijos, Svetainė, Organizacija and Sistema. The workspace picker is the only workspace switcher. Below `md` a context bar (current workspace · section, opening a section switcher, plus search) replaces the top bar and section tabs; the bottom bar keeps Pradžia, Užduotys, +, Pranešimai and Meniu. A focused form still gets the top bar on phones — it hosts the form's editor bar.
- A page a notification or email links to must let the user finish the job there, on a phone.
- A section may carry `children` (`SectionLink`, each with its own visibility) for sibling pages that share it. The tab then shows the active child's label, or a trail crumb, with a chevron listing every child; on phones the switcher and Meniu list them. Use it instead of in-page tab rows.
- Mano kelias is a plain Pradžia section, including the rep-facing step record; step management lives in Organizacija → Kelio žingsniai. Wiki articles appear as a crumb in the Rodyklė tab; its chevron links back to Rodyklė A–Ž. Article creation and editing belong to Wiki → Biblioteka.
