---
paths:
  - 'app/Providers/**'
---

# Providers

## Reset before re-registering into vendor static registries
Pest rebuilds the whole Application fresh per test, re-running every provider's boot()/register(). If a provider registers a closure into a vendor **static** (not container-scoped) registry — e.g. `LogActivityAction::beforeLogging()`, `TrimStrings::skipWhen()`, `Telescope::filter()` — that registry grows by one closure per test forever, and each closure implicitly pins that boot's Application alive (non-static closures in instance methods auto-capture `$this`).

Confirmed as the root cause of a severe test-suite slowdown (full sequential run 1400s→132s after fixing, see project_provider_static_leak_fix.md in memory). Fix pattern: call the package's reset API (`clearBeforeLoggingCallbacks()`, `flushState()`) immediately before re-registering, or add a `private static bool $registered` guard if no reset API exists (Telescope). Check this whenever wiring a new package's static registration hook in a provider.
