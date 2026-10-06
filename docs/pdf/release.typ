// Release notes PDF; build-release.ts passes the converted changelog page and its newest entry.
#import "templates/layout.typ": guide
#import "templates/title-page.typ": title-page
#import "templates/theme.typ": *
#import "components/page.typ": guide-page

#let major = sys.inputs.at("major", default: "v3")
#let title = "Pagrindiniai atnaujinimai"

#title-page(
  title: title,
  subtitle: sys.inputs.at("subtitle", default: "Mano VU SA atnaujinimai"),
  logo: "/public/logos/vusa.lin.hor.svg",
  date: sys.inputs.at("date", default: none),
)

#show: guide.with(title: title)

// Sections and roles are named, not numbered, so a reader can jump to theirs from the contents.
#set heading(numbering: none)

// Every main feature and role section starts a page, so a reader can print or hand on just theirs.
#show heading.where(level: 2): it => {
  pagebreak(weak: true)
  it
}

#{
  set page(header: none)
  show outline.entry.where(level: 1): it => { v(0.8em); strong(it) }
  text(size: 22pt, weight: "bold")[Turinys]
  outline(title: none, depth: 3, indent: 1.2em)
}

#pagebreak()
#guide-page(sys.inputs.at("source"), h1-level: 1)
