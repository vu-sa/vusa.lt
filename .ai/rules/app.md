---
paths:
  - 'app/**'
---

# App

## PHPStan nullsafe-under-coalesce false positive
PHPStan ≤2.2.9 reports `nullsafe.neverNull` ("Using nullsafe property access ?->x on left side of ?? is unnecessary. Use -> instead.") even when the receiver IS nullable (verified via \PHPStan\dumpType) — a known upstream false positive (fixed in phpstan-src 19dd4b0, regression in the 2.2 refactor). Do NOT apply the suggested `?->` → `->`: Laravel converts E_WARNING ("attempt to read property on null") into ErrorException, so the rewrite 500s. Restructure instead: hoist to a variable (`$x = $a?->b; $x ?? $c`) or narrow with an explicit null check. Upgrades alone don't fix it as of 2.2.9.
