#import "../templates/theme.typ": *

#let screenshot(src, caption: none, narrow: false, phone: false) = figure(
  block(stroke: 0.5pt + hairline, image(src, width: if phone { 35% } else if narrow { 60% } else { 100% })),
  caption: if caption != none { text(size: 9pt, fill: muted, caption) },
  kind: image,
  supplement: [Pav.],
)
