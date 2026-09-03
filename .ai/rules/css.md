---
paths:
  - resources/css/app.css
---

# Css

## Public surface: --brand vs --accent, --ink, radius trap
- `--accent` is shadcn's hover/muted surface (`hover:bg-accent`, `focus:bg-accent` on dropdown items/ghost buttons) — never repurpose it as the brand colour. Brand red (light) / amber (dark) lives in `--brand` / `--brand-fill` / `--brand-foreground` (`text-brand`, `bg-brand-fill`).
- `--ink` (`:root`) is the fixed-dark ground for surfaces that lay white type over a photo — hero, mega-menu featured card, calendar scrims. Deliberately NOT theme-swapped (stays dark in light mode too, since the scrim is what keeps the copy legible). Use `bg-ink` / `from-ink` / `via-ink`, never raw `black` / `zinc-900`, for that treatment.
- The public radius scale (`--radius-*`, zeroed inside `[data-surface="public"]`) lives in plain `@theme`, not `@theme inline`. An `inline` block precomputes `calc()` into a literal at build time, so a scoped override of the same var does nothing — this bit the redesign once already. `rounded-full` is a literal too and survives the zeroed scale regardless; override it explicitly with `rounded-none` per component (`Avatar` is the recurring offender).
