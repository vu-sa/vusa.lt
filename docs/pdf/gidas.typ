#import "templates/layout.typ": guide
#import "templates/title-page.typ": title-page
#import "templates/theme.typ": *

#let title = "vusa.lt gidas"

#title-page(
  title: title,
  subtitle: "Mano VU SA platformos žinynas administratoriams",
  logo: "/public/logos/vusa.lin.hor.svg",
  date: datetime.today().display("[year]-[month]-[day]"),
)

#show: guide.with(title: title)

#{
  set page(header: none)
  show outline.entry.where(level: 1): it => { v(0.8em); strong(it) }
  text(size: 22pt, weight: "bold")[Turinys]
  outline(title: none, depth: 2, indent: 1.2em)
}

#include ".build/content.typ"
