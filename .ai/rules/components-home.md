---
paths:
  - 'resources/js/Pages/Admin/ShowAdminHome.vue,resources/js/Components/Home/**'
---

# Components Home

## Pradžia home hierarchy
On Pradžia, the hero (`HomeHero`) is a short, decorative photo band: a colour photo under the public hero's "strong" scrim built from `--background` (paper in light mode, near-black in dark), so text and buttons use the normal theme tokens; today's date and a small `u-display` "Labas, …" h1 at the bottom left, and the main tenant's newest news (quiet title + small outline links) at the bottom right; a community photo stands in when there is none. Below `sm` there is no band — only the date and greeting. It must never compete with the tasks below it. Quick-action buttons are bold sentence case, with only the first available action brand-filled; home section headings use a brand icon and tracked uppercase text. Task due dates stay relative, and an overdue task colours only its date instead of adding a status badge. These home-specific choices override the general admin button and section-heading case defaults.
