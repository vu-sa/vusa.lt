---
paths:
  - 'resources/js/Pages/Admin/ShowAdminHome.vue'
  - 'resources/js/Components/Home/**'
---

# Admin home (Pradžia)

## Pradžia home hierarchy
On Pradžia, the hero (`HomeHero`) is a short, decorative photo band: a colour photo under the public hero's "strong" scrim built from `--background` (paper in light mode, near-black in dark), so text and buttons use the normal theme tokens; today's date and a small `u-display` "Labas, …" h1 at the bottom left (no task summary — the task list says it), and the main tenant's newest news (quiet title + small outline links) at the bottom right; a community photo stands in when there is none. Below `sm` there is no band — only the date and greeting. It must never compete with the tasks below it. Home section headings use a brand icon and tracked uppercase text. Task due dates stay relative, and an overdue task colours only its date instead of adding a status badge. These home-specific choices override the general admin button and section-heading case defaults.

## One grid, one boxed list per section
Pradžia is one `lg:grid-cols-[1.4fr_1fr]` grid with Eiti į tiles (`QuickAccess`, `NavigationTiles framed`) full width below it. Main column: tasks → activity requests → institutions → reservation draft → meetings → events and news (one section, told apart by icon and word) → status list. Sidebar, on every width (it stacks after the main column on phones): Greiti veiksmai first, then followed and recently edited.
Every section is an `OverviewSection` with `content-ruled` over a `CollectionRowList frame="box"`; a row that is one link uses `ROW_LINK_CLASS`. Statuses sit in the sub-line as `Patterns/StatusText`, never as a tag beside a button. Greiti veiksmai are rows, not buttons: the first leads in brand ink, without a fill. A row with one job ("Fiksuoti veiklą", "Atsakyti") keeps its labelled `CollectionRowActions` beside the title at every width instead of folding it into ⋯. "Show all" lives in the section header (`SectionHeaderAction`), never as a button under the list.
