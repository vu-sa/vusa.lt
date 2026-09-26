#import "../templates/theme.typ": *

// A dated pointer from a guide section to the release that changed it (web: ChangelogNote.vue).
#let changelog-note(version: "", date: "", title: "", href: "", body) = block(
  width: 100%,
  inset: (x: 14pt, y: 12pt),
  above: 1.6em,
  below: 1.6em,
  stroke: (paint: amber.darken(15%), thickness: 0.8pt, dash: "dashed"),
  breakable: false,
)[
  #set par(spacing: 1em)
  #box(fill: amber, inset: (x: 5pt, y: 2.5pt), text(size: 8.5pt, weight: "bold")[Atnaujinta #version])
  #h(6pt)
  #text(size: 8.5pt, fill: muted, date)
  #v(0.2em)
  #text(weight: "bold", size: 9.5pt, title)

  #body

  #text(size: 9pt, link(href)[Visi #version pakeitimai →])
]
