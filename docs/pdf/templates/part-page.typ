#import "theme.typ": *

// A full-bleed divider page per workspace; the level-1 heading on it drives the outline and running header.
#let part-page(title: "", description: none) = {
  pagebreak(weak: true)
  page(margin: 0mm, fill: ink, header: none, footer: none)[
    #place(left + bottom, rect(width: 100%, height: 6mm, fill: amber))
    #place(left + bottom, dx: 28mm, dy: -40mm, block(width: 150mm)[
      #set text(fill: white)
      #show heading: it => text(size: 40pt, weight: "bold", it)
      #heading(level: 1, title)
      #if description != none {
        v(4mm)
        text(size: 14pt, fill: amber)[#description]
      }
    ])
  ]
}
