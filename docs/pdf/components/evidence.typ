#import "../templates/theme.typ": *

// The `tests:` frontmatter, as on the website's "Įrodyta testais" block.
#let evidence(tests: (), reviewed: none) = if tests.len() > 0 {
  v(2.2em)
  block(width: 100%, inset: (x: 12pt, y: 10pt), stroke: (left: 3pt + amber), fill: paper, breakable: false)[
    #text(weight: "bold", size: 9pt)[Įrodyta testais]
    #if reviewed != none { text(size: 9pt, fill: muted)[ · peržiūrėta #reviewed] }
    #v(0.3em)
    #for (label, prefix) in (("Serveris – taisyklės ir teisės", "tests/"), ("Sąsaja – ką rodo ir leidžia ekranas", "resources/js/")) {
      let group = tests.filter(test => test.starts-with(prefix))
      if group.len() > 0 {
        v(0.4em)
        text(size: 8.5pt, fill: muted, label)
        set text(font: mono, size: 7.5pt)
        for test in group [
          - #link("https://github.com/vu-sa/vusa.lt/blob/main/" + test, test)
        ]
      }
    }
  ]
}
