#import "../templates/theme.typ": *

#let evidence(tests: ()) = if tests.len() > 0 {
  v(2.2em)
  block(width: 100%, inset: (x: 12pt, y: 10pt), stroke: (left: 3pt + amber), fill: paper, breakable: true)[
    #text(weight: "bold", size: 9pt)[Testų nuorodos]
    #parbreak()
    #text(size: 8.5pt, fill: muted)[Šie testai tikrina atskiras elgsenos dalis. Jie nepakeičia turinio peržiūros.]
    #v(0.3em)
    #let browser = test => test.starts-with("tests/Browser/")
    #for (label, belongs) in (
      ("Serveris – taisyklės ir teisės", test => test.starts-with("tests/") and not browser(test)),
      ("Sąsaja – ką rodo ir leidžia ekranas", test => test.starts-with("resources/js/")),
      ("Naršyklė – kaip veikia tikrame ekrane", browser),
    ) {
      let group = tests.filter(belongs)
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
