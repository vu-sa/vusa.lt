---
paths:
  - resources/css/app.css
  - 'resources/css/**/*.css'
---

# Css

## Public surface: --brand vs --accent, --ink, radius trap
- `--accent` is shadcn's hover/muted surface (`hover:bg-accent`, `focus:bg-accent` on dropdown items/ghost buttons) — never repurpose it as the brand colour. Brand red (light) / amber (dark) lives in `--brand` / `--brand-fill` / `--brand-foreground` (`text-brand`, `bg-brand-fill`).
- `--ink` (`:root`) is the fixed-dark ground for surfaces that lay white type over a photo — hero, mega-menu featured card, calendar scrims. Deliberately NOT theme-swapped (stays dark in light mode too, since the scrim is what keeps the copy legible). Use `bg-ink` / `from-ink` / `via-ink`, never raw `black` / `zinc-900`, for that treatment.
- The public radius scale (`--radius-*`, zeroed inside `[data-surface="public"]`) lives in plain `@theme`, not `@theme inline`. An `inline` block precomputes `calc()` into a literal at build time, so a scoped override of the same var does nothing — this bit the redesign once already. `rounded-full` is a literal too and survives the zeroed scale regardless; override it explicitly with `rounded-none` per component (`Avatar` is the recurring offender).

## app.css split into partials — find content by area, not by grepping app.css
`resources/css/app.css` is now a thin entry that only `@import`s partials (still a single Tailwind build — no bundle-size split between admin/public, see below). Content lives in:
- `theme/base-tokens.css` — @custom-variant, radius @theme, brand @theme inline, :root/.dark palette, dark-mode-init
- `theme/design-tokens.css` — consolidated @theme inline (fonts, vusa-*/status-* colors, ui/sidebar/chart mapping, animations)
- `public/surface.css` — [data-surface="public"] palette (light+dark), a11y menu, reading-scale — the --brand vs --accent / --ink / radius-trap gotchas below live here now
- `public/typography.css` — .typography, .rc-prose(-editing), .rc-lead, .rc-h-*, .rc-tag-*
- `public/canvas.css` — .rc-canvas grid, .rc-shell/.rc-aside, .wrapper*, .full-bleed, .rc-viewport
- `base/reset.css` — Tailwind v3 border-color compat, base h1-h4, .icon-inline, base layer
- `base/view-transitions.css` — View Transitions API rules
- `components/misc.css`, `vendor/vue-flow.css` — small standalone/vendor overrides

Admin-only plain CSS (not Tailwind-generated) still lives in `admin.css`/`driver-tour.css`, loaded separately via `admin.ts` — don't move Tailwind `@theme` tokens there, since only `app.css`'s import graph is a Tailwind entry point; a token declared outside it never compiles into a utility (e.g. `--font-admin` must stay in `theme/design-tokens.css` for `.font-admin` to exist at all, even though Inter is admin-only in practice).

## Admin colour system: shape carries the meaning
Tokens live in `resources/css/theme/design-tokens.css`. Shape tells the three kinds apart:
- Solid fill is brand (`--brand`/`--brand-fill`): one primary action per region, plus the current-location marker.
- A tinted badge with icon and word is a status (`--status-{neutral|info|progress|attention|success|danger}` + `-surface`/`-border`), rendered through `StatusBadge`.
- A small mark is a category (`--cat-1…8` + `-surface`): entity types, event types, units, chart series. Never used for status.
Picking a status role: healthy default → no badge; draft, cancelled or archived → neutral; submitted or upcoming → info; in progress → progress; needs action or missing info → attention; done or approved → success; overdue, rejected or failed → danger. Never colour alone. No large coloured surfaces except the neutral ink band.
Gates: `designTokens.test.ts` guards the token list (both themes, cat hues ≥25° apart); `Patterns/ColourSystem.stories.ts` (a11y `error`) guards 4.5:1 contrast via `npm run test:storybook`.
Traps: `--accent` is shadcn's hover surface, not brand; keep the radius scale in plain `@theme` (not `@theme inline`); a `dark:` class beats an unprefixed one, so overrides need their `dark:` twin; `rounded-full` survives the zeroed radius scale.
