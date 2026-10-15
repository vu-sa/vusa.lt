#import "theme.typ": *

#let guide(title: "", body) = {
  set document(title: title, author: "VU Studentų atstovybė")
  set text(font: sans, size: 10.5pt, lang: "lt", fill: ink)
  set par(justify: false, leading: 0.78em, spacing: 1.35em)
  set list(indent: 0.4em, body-indent: 0.6em, spacing: 0.8em)
  set enum(indent: 0.4em, body-indent: 0.6em, spacing: 0.8em)
  show list: set block(above: 1.1em, below: 1.35em)
  show enum: set block(above: 1.1em, below: 1.35em)
  show figure: set block(above: 1.8em, below: 1.8em)

  set page(
    paper: "a4",
    margin: (x: 22mm, top: 24mm, bottom: 22mm),
    header: context {
      if counter(page).get().first() > 2 {
        let chapters = query(selector(heading.where(level: 1)).before(here()))
        set text(size: 8pt, fill: muted)
        grid(
          columns: (1fr, auto),
          title,
          if chapters.len() > 0 { upper(chapters.last().body) },
        )
        v(-0.4em)
        line(length: 100%, stroke: 0.5pt + hairline)
      }
    },
    footer: context {
      if counter(page).get().first() > 1 {
        set text(size: 8pt, fill: muted)
        align(right, counter(page).display())
      }
    },
  )

  // Chapters and pages are numbered; the headings inside a page are not.
  set heading(numbering: (..nums) => if nums.pos().len() <= 2 { numbering("1.1", ..nums) })

  show heading.where(level: 1): it => {
    set text(size: 22pt, weight: "bold")
    block(below: 1em, it)
  }
  show heading.where(level: 2): it => {
    set text(size: 17pt, weight: "bold")
    block(below: 0.4em, it)
    line(length: 3em, stroke: 2.5pt + amber)
    v(0.6em)
  }
  // Unnumbered, so `it.body`: rendering `it` keeps the numbering gap even when the number is none.
  show heading.where(level: 3): it => block(
    above: 2.2em,
    below: 1em,
    inset: (left: 8pt, y: 2pt),
    stroke: (left: 3pt + amber),
    text(size: 12.5pt, weight: "bold", it.body),
  )
  show heading.where(level: 4): it => block(above: 1.6em, below: 0.8em, text(size: 10.5pt, weight: "bold", it.body))

  // Red is reserved for links, so a reader can always tell what is clickable.
  show link: it => text(fill: red, it)

  show raw: set text(font: mono, size: 0.9em)
  show raw.where(block: false): it => box(fill: paper, outset: (y: 2pt), inset: (x: 2pt), it)
  show raw.where(block: true): it => block(width: 100%, fill: paper, inset: 12pt, above: 1.4em, below: 1.6em, stroke: (left: 2pt + hairline), it)

  set table(stroke: (_, y) => (bottom: 0.5pt + hairline, top: if y == 0 { 1pt + ink }), inset: (x: 7pt, y: 7pt))
  show table.cell.where(y: 0): set text(weight: "bold")
  show table: set text(size: 9.5pt)
  show table: set par(justify: false, leading: 0.65em)
  show table: set block(above: 1.4em, below: 1.6em)

  show quote.where(block: true): it => block(inset: (left: 12pt, y: 4pt), stroke: (left: 2pt + amber), it.body)

  body
}
