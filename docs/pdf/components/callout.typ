#import "../templates/theme.typ": *

// Mirrors VitePress `::: info | tip | warning | danger | details` containers.
#let callout-styles = (
  info: (bar: muted, label: "Informacija"),
  tip: (bar: amber, label: "Patarimas"),
  warning: (bar: rgb("#d97706"), label: "Dėmesio"),
  danger: (bar: red, label: "Svarbu"),
  details: (bar: hairline, label: "Plačiau"),
)

#let callout(type: "info", title: none, body) = {
  let style = callout-styles.at(type, default: callout-styles.info)
  block(
    width: 100%,
    fill: paper,
    stroke: (left: 3pt + style.bar),
    inset: (x: 14pt, y: 12pt),
    above: 1.6em,
    below: 1.6em,
    breakable: true,
  )[
    #set par(spacing: 1em)
    // Sticky so a title never ends a page without its body.
    #block(sticky: true, below: 0.8em, text(weight: "bold", size: 9.5pt)[#if title != none and title != "" { title } else { style.label }])
    #body
  ]
}
