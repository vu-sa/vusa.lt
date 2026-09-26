#import "../templates/theme.typ": *

#let screenshot(src, caption: none, narrow: false) = figure(
  block(stroke: 0.5pt + hairline, image(src, width: if narrow { 60% } else { 100% })),
  caption: if caption != none { text(size: 9pt, fill: muted, caption) },
  kind: image,
  supplement: [Pav.],
)
