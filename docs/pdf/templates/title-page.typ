#import "theme.typ": *

#let title-page(title: "", subtitle: "", logo: none, date: none) = page(
  margin: 0mm,
  fill: paper,
  header: none,
  footer: none,
)[
  #set text(font: sans, fill: ink)
  #place(left + top, rect(width: 8mm, height: 100%, fill: red))
  #place(left + top, dx: 28mm, dy: 26mm, if logo != none { image(logo, width: 55mm) })
  #place(left + top, dx: 28mm, dy: 40%, block(width: 150mm)[
    #text(size: 46pt, weight: "bold", tracking: -0.5pt)[#title]
    #v(6mm)
    #line(length: 24mm, stroke: 4pt + amber)
    #v(6mm)
    #text(size: 15pt, fill: muted)[#subtitle]
  ])
  #if date != none {
    place(left + bottom, dx: 28mm, dy: -24mm, text(size: 10pt, fill: muted)[#date])
  }
]
