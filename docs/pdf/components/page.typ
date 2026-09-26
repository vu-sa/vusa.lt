#import "@preview/cmarker:0.1.9"
#import "callout.typ": callout
#import "screenshot.typ": screenshot
#import "changelog-note.typ": changelog-note

// Headings get VitePress-compatible labels from build.ts, so cmarker's own (GitHub-style) ones are off.
#let guide-page(path, h1-level: 2) = cmarker.render(
  read(path),
  h1-level: h1-level,
  heading-labels: none,
  prefix-label-uses: false,
  html: (
    callout: (attrs, body) => callout(type: attrs.at("type", default: "info"), title: attrs.at("title", default: none), body),
    changelog: (attrs, body) => changelog-note(version: attrs.version, date: attrs.date, title: attrs.title, href: attrs.href, body),
    screenshot: ("void", attrs => screenshot(attrs.src, caption: attrs.at("caption", default: none), narrow: "narrow" in attrs)),
  ),
)
